<?php
namespace BRG;
use BRG\Core\Globals;
use BRG\Core\Notifications;
use BRG\Helpers\Utils;
use BRG\Managers\Meeples;

class Map
{
  protected static $map = null;
  public static function init()
  {
    $mapId = Globals::getMap();
    if ($mapId == 0) {
      return;
    }

    $classes = [
      \MAP_BASE => 'BaseMap',
    ];

    $className = 'BRG\Maps\\' . $classes[$mapId];
    static::$map = new $className();
  }

  public static function getUiData()
  {
    return [
      'id' => self::getId(),
      'headstreams' => Globals::getHeadstreams(),
      'bonusTiles' => Globals::getBonusTiles(),
      'conduits' => self::getConduits(),
      'powerhouses' => array_values(self::getPowerhouses()),
      'basins' => array_values(self::getBasins()),
    ];
  }

  /*
   * Magic method that intercept not defined static method and do the appropriate stuff
   */
  public static function __callStatic($method, $args)
  {
    if (method_exists(self::$map, $method)) {
      return self::$map->$method(...$args);
    } else {
      throw new \InvalidArgumentException("No such function in Map : {$method}");
    }
  }

  /**
   * Setup 11] place one neutral dam on each Area of the map
   */
  public static function placeNeutralDams()
  {
    // 11] Put random neutral dams
    $basins = self::$map->getBasinsByArea();
    $meeples = [];
    foreach (AREAS as $i => $area) {
      // Keep only bottom basins
      Utils::filter($basins[$area], function ($basin) {
        return $basin['cost'] == 0;
      });
      // Pick a random one
      $key = array_rand($basins[$area]);
      $basin = $basins[$area][$key];

      // Create the base and elevations
      $meeples[] = ['type' => BASE, 'company_id' => COMPANY_NEUTRAL, 'location' => $basin['id']];
      for ($j = 0; $j < $i; $j++) {
        $meeples[] = ['type' => ELEVATION, 'company_id' => COMPANY_NEUTRAL, 'location' => $basin['id']];
      }

      // Add droplet
      $meeples[] = ['type' => DROPLET, 'location' => $basin['id']];
    }
    Meeples::create($meeples);
  }

  /**
   * At each start of round, place droplets on headstreams
   */
  protected static $headstreamTiles = [
    HT_1 => [0, 2, 2, 0],
    HT_2 => [0, 0, 1, 3],
    HT_3 => [2, 0, 2, 0],
    HT_4 => [1, 1, 1, 1],
    HT_5 => [0, 1, 0, 2],
    HT_6 => [0, 0, 1, 2],
    HT_7 => [1, 0, 2, 2],
    HT_8 => [0, 2, 1, 2],
  ];

  public static function fillHeadstreams()
  {
    $round = Globals::getRound();
    $headstreams = Globals::getHeadstreams();
    $droplets = [];
    foreach ($headstreams as $hId => $tileId) {
      $n = static::$headstreamTiles[$tileId][$round - 1];
      if ($n > 0) {
        $meeples[] = ['type' => DROPLET, 'location' => $hId, 'nbr' => $n];
      }
    }

    return Meeples::getMany(Meeples::create($meeples));
  }

  /////////////////////////////////////////////
  //  __  __                 _
  // |  \/  | ___  ___ _ __ | | ___  ___
  // | |\/| |/ _ \/ _ \ '_ \| |/ _ \/ __|
  // | |  | |  __/  __/ |_) | |  __/\__ \
  // |_|  |_|\___|\___| .__/|_|\___||___/
  //                  |_|
  /////////////////////////////////////////////

  public function countDamsInBasin($basin)
  {
    return Meeples::getFilteredQuery(null, $basin, [BASE, \ELEVATION])->count();
  }

  public function getDropletsInBasin($basin)
  {
    return Meeples::getFilteredQuery(null, $basin, [DROPLET])->get();
  }

  public function countDropletsInBasin($basin)
  {
    return self::getDropletsInBasin($basin)->count();
  }

  ////////////////////////////////////////////////////////////
  // __        __    _              _____ _
  // \ \      / /_ _| |_ ___ _ __  |  ___| | _____      __
  //  \ \ /\ / / _` | __/ _ \ '__| | |_  | |/ _ \ \ /\ / /
  //   \ V  V / (_| | ||  __/ |    |  _| | | (_) \ V  V /
  //    \_/\_/ \__,_|\__\___|_|    |_|   |_|\___/ \_/\_/
  //
  ////////////////////////////////////////////////////////////

  public function flowDroplets($droplets)
  {
    foreach ($droplets as &$droplet) {
      $path = self::getFlowPath($droplet);
      $droplet['path'] = $path;
      $location = $path[count($path) - 1];

      // Check whether the last location is EXIT or not
      if ($location == 'EXIT') {
        Meeples::DB()->delete($droplet['id']);
      } else {
        Meeples::move($droplet['id'], $location);
      }
    }

    Notifications::moveDroplets($droplets);

    // TODO : handle company that gain thing when water pass by powerhouse
    // => postpone the notifications in this case somehow !
  }

  public function getFlowPath($droplet)
  {
    // Sanity check
    if (!is_array($droplet)) {
      $droplet = Meeples::get($droplet);
      if ($droplet == null) {
        throw new \BgaVisibleSystemException("Droplet doesn't exist. shouldn't happen");
      }
    }
    $location = $droplet['location'];
    $path = [];
    $blocked = false;
    $rivers = self::getRivers();
    do {
      // Search for the next location
      if ($location[0] == 'P') {
        $location = explode('_', $location)[0];
      }

      $basin = $rivers[$location] ?? null;
      if (\is_null($basin)) {
        throw new \feException($location);
        throw new \BgaVisibleSystemException('Unknown route for droplet. Should not happen');
      }

      // Move the droplet to that location
      $location = $basin;
      $path[] = $basin;

      // If location is EXIT or Droplet is blocked by dam, stop here
      // TODO : handle company that can hold 4 droplet with 3 elevation or sthg like that
      if ($basin == 'EXIT' || self::countDropletsInBasin($basin) < self::countDamsInBasin($basin)) {
        $blocked = true;
      }
    } while (!$blocked);

    return $path;
  }

  /////////////////////////////////////////////////////////////
  //  ____                _            _   _
  // |  _ \ _ __ ___   __| |_   _  ___| |_(_) ___  _ __
  // | |_) | '__/ _ \ / _` | | | |/ __| __| |/ _ \| '_ \
  // |  __/| | | (_) | (_| | |_| | (__| |_| | (_) | | | |
  // |_|   |_|  \___/ \__,_|\__,_|\___|\__|_|\___/|_| |_|
  //
  /////////////////////////////////////////////////////////////
  public function getProductionSystems($company, $bonus)
  {
    $credits = $company->countReserveResource(CREDIT);
    $systems = [];
    foreach (self::getZones() as $zoneId => $zone) {
      // Compute the possible conduits
      $conduits = [];
      foreach ($zone['conduits'] ?? [] as $sId => $conduit) {
        // Is this conduit built by someone ?
        $meeple = Meeples::getOnSpace($sId, CONDUIT)->first();
        if (is_null($meeple)) {
          continue;
        }

        // Is it linked to a powerhouse built by the company ?
        $endingSpace = 'P' . $conduit['end'] . '%'; // Any powerhouse in the ending zone
        $powerhouse = Meeples::getOnSpace($endingSpace, POWERHOUSE, $company)->first();
        if (is_null($powerhouse)) {
          continue;
        }

        $conduits[$sId] = [
          'conduitOwnerId' => $meeple['cId'],
          'conduitSpaceId' => $sId,
          'powerhouseSpaceId' => $powerhouse['location'],
          'conduitProduction' => $conduit['production'],
        ];
      }

      // Compute the possible dams
      $dams = [];
      foreach ($zone['basins'] ?? [] as $basin) {
        $dam = Meeples::getOnSpace($basin, BASE, [COMPANY_NEUTRAL, $company])->first();
        $nDroplets = self::countDropletsInBasin($basin);
        if (is_null($dam) || $nDroplets == 0) {
          continue;
        }

        $dams[] = [
          'basin' => $basin,
          'droplets' => $nDroplets,
        ];
      }

      // Take all the pair to have corresponding systems for that zone
      foreach ($conduits as $conduit) {
        foreach ($dams as $dam) {
          // Filter number of usable droplets for paying conduits
          $maxDroplets = $dam['droplets'];
          if ($conduit['conduitOwnerId'] != $company->getId()) {
            $maxDroplets = min($credits, $maxDroplets);
          }

          // Compute potential energy production
          $system = array_merge($dam, $conduit);
          $system['productions'] = [];

          for ($i = 1; $i <= $maxDroplets; $i++) {
            $energy = $system['conduitProduction'] * $i;
            if ($company->isXO(\XO_VIKTOR)) {
              $energy = max($energy, 4);
            }
            $energy += $bonus;

            if ($energy > 0) {
              $system['productions'][$i] = $energy;
            }
          }

          // Add it to the list if at least one possible > 0 production
          if (!empty($system['productions'])) {
            $systems[] = $system;
          }
        }
      }
    }

    return $systems;
  }
}
