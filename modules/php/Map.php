<?php
namespace BRG;
use BRG\Core\Globals;
use BRG\Core\Notifications;
use BRG\Helpers\Utils;
use BRG\Managers\Meeples;
use BRG\Actions\Gain;
use BRG\Managers\Companies;
use BRG\Helpers\Collection;

class Map
{
  protected static $map = null;
  protected static $infos = null;
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

    self::$infos = [];
    foreach (self::getBasins() as $bId => $info) {
      self::$infos[$bId] = $info;
    }
    foreach (self::getConduits() as $cId => $info) {
      self::$infos[$cId] = $info;
    }
    foreach (self::getPowerhouses() as $pId => $info) {
      self::$infos[$pId] = $info;
    }
    foreach (self::getHeadstreamTiles() as $hId => $info) {
      self::$infos[$hId] = $info;
    }

    self::refresh();
  }

  public static function getUiData()
  {
    return [
      'id' => self::getId(),
      'headstreams' => self::getHeadstreamTiles(),
      'bonusTiles' => Globals::getBonusTiles(),
      'conduits' => self::getConduits(),
      'powerhouses' => array_values(self::getPowerhouses()),
      'basins' => array_values(self::getBasins()),
      'zoneIds' => array_keys(self::getZones()),
      'exits' => self::getExits(),
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
    HT_8 => [0, 1, 2, 1],
  ];

  public static function getHeadstreamTiles()
  {
    $tiles = [];
    foreach (Globals::getHeadstreams() as $hId => $tId) {
      $tiles[$hId] = [
        'tileId' => $tId,
        'droplets' => self::$headstreamTiles[$tId],
      ];
    }

    return $tiles;
  }

  public static function fillHeadstreams()
  {
    $round = Globals::getRound();
    $headstreams = Globals::getHeadstreams();
    $droplets = [];
    foreach ($headstreams as $hId => $tileId) {
      $n = static::$headstreamTiles[$tileId][$round - 1];
      if ($n > 0) {
        $droplets[] = ['type' => DROPLET, 'location' => $hId, 'nbr' => $n];
      }
    }
    if (count($droplets) > 0) {
      $meeples = Meeples::getMany(Meeples::create($droplets));
      foreach ($meeples as $meeple) {
        self::$infos[$meeple['location']]['droplets'][] = $meeple;
      }
      return $meeples;
    } else {
      return new Collection();
    }
  }

  ///////////////////////////////////
  //   ____           _
  //  / ___|__ _  ___| |__   ___
  // | |   / _` |/ __| '_ \ / _ \
  // | |__| (_| | (__| | | |  __/
  //  \____\__,_|\___|_| |_|\___|
  //
  ///////////////////////////////////

  // Compute all meeples on the map and cache them
  public static function refresh()
  {
    foreach (self::$infos as $spaceId => &$info) {
      unset($info['structures']);
      unset($info['droplets']);
    }

    foreach (Meeples::getOnMap() as $meeple) {
      if (isset(self::$infos[$meeple['location']])) {
        if (in_array($meeple['type'], \STRUCTURES)) {
          self::$infos[$meeple['location']]['structures'][] = $meeple;
        } elseif ($meeple['type'] == DROPLET) {
          self::$infos[$meeple['location']]['droplets'][] = $meeple;
        }
      }
    }

    self::updateBasinsCapacities();
  }

  // Cache basin capacity
  public static function updateBasinsCapacities()
  {
    $cIds = Companies::getAll()
      ->filter(function ($company) {
        return $company->isXO(\XO_GRAZIANO);
      })
      ->getIds();

    foreach (self::getBasins() as $bId => $info) {
      $dams = self::$infos[$bId]['structures'] ?? [];
      $capacity = count($dams);
      if ($capacity == 3 && in_array($dams[0]['cId'], $cIds)) {
        $capacity = 4;
      }

      self::$infos[$bId]['capacity'] = $capacity;
    }
  }

  //     _       _     _
  //    / \   __| | __| | ___ _ __ ___
  //   / _ \ / _` |/ _` |/ _ \ '__/ __|
  //  / ___ \ (_| | (_| |  __/ |  \__ \
  // /_/   \_\__,_|\__,_|\___|_|  |___/
  //

  public static function placeStructure($meeple, $spaceId)
  {
    self::$infos[$spaceId]['structures'][] = $meeple;
    self::updateBasinsCapacities();

    if ($meeple['type'] == BASE && !is_null(self::$constructSlots)) {
      self::$constructSlots[$spaceId]['type'] = ELEVATION;
    }
  }

  public static function addDroplets($droplets)
  {
    foreach ($droplets as $droplet) {
      self::$infos[$droplet['location']]['droplets'][] = $droplet;
    }
  }

  //   ____      _   _
  //  / ___| ___| |_| |_ ___ _ __ ___
  // | |  _ / _ \ __| __/ _ \ '__/ __|
  // | |_| |  __/ |_| ||  __/ |  \__ \
  //  \____|\___|\__|\__\___|_|  |___/

  //////////////////////////////////////
  // Droplet Utils
  //////////////////////////////////////
  public function getDropletsInBasin($basin)
  {
    return self::$infos[$basin]['droplets'] ?? [];
  }

  public function removeDropletsInBasin($basin, $nDroplets)
  {
    $droplets = [];
    $remaining = [];
    foreach (self::getDropletsInBasin($basin) as $droplet) {
      if (count($droplets) == $nDroplets) {
        $remaining[] = $droplet;
      } else {
        $droplets[] = $droplet;
      }
    }
    self::$infos[$basin]['droplets'] = $remaining;
    return $droplets;
  }

  public function countDropletsInBasin($basin)
  {
    return count(self::getDropletsInBasin($basin));
  }

  public function getBasinCapacity($basin)
  {
    return self::$infos[$basin]['capacity'] ?? 0;
  }

  //////////////////////////////////////
  // Structure Utils
  //////////////////////////////////////
  public function getBuiltStructures($spaceId, $company = null)
  {
    $spaceIds = \is_array($spaceId) ? $spaceId : [$spaceId];
    $companies = is_null($company) ? [] : (is_array($company) ? $company : [$company]);
    $cIds = array_map(function ($c) {
      return is_int($c) ? $c : $c->getId();
    }, $companies);

    $structures = [];
    foreach ($spaceIds as $sId) {
      foreach (self::$infos[$sId]['structures'] ?? [] as $structure) {
        if (is_null($company) || in_array($structure['cId'], $cIds)) {
          $structures[] = $structure;
        }
      }
    }

    return $structures;
  }

  public function getBuiltStructure($spaceId, $company = null)
  {
    $structures = self::getBuiltStructures($spaceId, $company);
    return empty($structures) ? null : $structures[0];
  }

  public function getBuiltDamsInZone($zoneId, $company = null)
  {
    $basins = self::getZones()[$zoneId]['basins'];
    return self::getBuiltStructures($basins, $company);
  }

  //////////////////////////////////////
  // Powerhouses Utils
  //////////////////////////////////////
  public function getLinkedPowerhousesSpaces($conduitId)
  {
    return self::$infos[$conduitId]['powerhouses'];
  }

  public function getLinkedPowerhouses($conduitId, $company = null)
  {
    $spaceIds = self::getLinkedPowerhousesSpaces($conduitId);
    return self::getBuiltStructures($spaceIds, $company);
  }

  public function getLinkedPowerhouse($conduitId, $company = null)
  {
    $powerhouses = self::getLinkedPowerhouses($conduitId, $company);
    return empty($powerhouses) ? null : $powerhouses[0];
  }

  public function getBuiltPowerhousesInZone($zoneId, $company = null)
  {
    $spaceIds = self::getPowerhousesInZone($zoneId);
    return self::getBuiltStructures($spaceIds, $company);
  }

  ///////////////////////////////////////////////////////
  //   ____                _                   _
  //  / ___|___  _ __  ___| |_ _ __ _   _  ___| |_
  // | |   / _ \| '_ \/ __| __| '__| | | |/ __| __|
  // | |__| (_) | | | \__ \ |_| |  | |_| | (__| |_
  //  \____\___/|_| |_|___/\__|_|   \__,_|\___|\__|
  //
  ///////////////////////////////////////////////////////
  protected static $constructSlots = null;
  public function getConstructSlots()
  {
    if (!is_null(self::$constructSlots)) {
      return self::$constructSlots;
    }

    $slots = [];
    foreach (self::getBasins() as $bId => $basin) {
      $basin['type'] = is_null(self::getBuiltStructure($bId)) ? BASE : ELEVATION;
      $slots[$bId] = $basin;
    }

    foreach (self::getPowerhouses() as $pId => $powerhouse) {
      $powerhouse['type'] = POWERHOUSE;
      $slots[$pId] = $powerhouse;
    }

    foreach (self::getConduits() as $cId => $conduit) {
      $conduit['type'] = CONDUIT;
      $slots[$cId] = $conduit;
    }

    self::$constructSlots = $slots;
    return $slots;
  }

  ////////////////////////////////////////////////////////////
  // __        __    _              _____ _
  // \ \      / /_ _| |_ ___ _ __  |  ___| | _____      __
  //  \ \ /\ / / _` | __/ _ \ '__| | |_  | |/ _ \ \ /\ / /
  //   \ V  V / (_| | ||  __/ |    |  _| | | (_) \ V  V /
  //    \_/\_/ \__,_|\__\___|_|    |_|   |_|\___/ \_/\_/
  //
  ////////////////////////////////////////////////////////////
  public function getUSAPowerhouses()
  {
    // If production power of USA is enabled
    $usa = Companies::get(COMPANY_USA);
    if ($usa != null && $usa->productionPowerEnabled()) {
      $USAPowerHouses = $usa
        ->getBuiltStructures(\POWERHOUSE)
        ->map(function ($m) {
          return explode('_', $m['location'])[0];
        })
        ->toArray();
    } else {
      $USAPowerHouses = [];
    }

    return $USAPowerHouses;
  }

  public function flowDroplets($droplets)
  {
    $USAPowerHouses = self::getUSAPowerhouses();
    $bonusEnergy = 0;
    $movedDroplets = new Collection([]);
    foreach ($droplets as &$droplet) {
      list($path, $energy) = self::getFlowPath($droplet, $USAPowerHouses);
      $droplet['path'] = $path;
      if (count($path) == 0) {
        continue;
      }
      // Remove drop from initial location
      $location = $droplet['location'];
      self::$infos[$location]['droplets'] = array_values(
        array_filter(self::$infos[$location]['droplets'] ?? [], function ($meeple) use ($droplet) {
          return $meeple['id'] != $droplet['id'];
        })
      );

      // Add it to final location
      $location = $path[count($path) - 1];
      $bonusEnergy += $energy;

      // Check whether the last location is EXIT or not
      if (in_array($location, Map::getExits())) {
        Meeples::DB()->delete($droplet['id']);
      } else {
        Meeples::move($droplet['id'], $location);
        self::$infos[$location]['droplets'][] = $droplet;
      }
      $movedDroplets[] = $droplet;
    }

    Notifications::moveDroplets($movedDroplets);

    // USA power
    if ($bonusEnergy > 0) {
      Gain::gainResources(
        Companies::get(COMPANY_USA),
        [ENERGY => $bonusEnergy],
        null,
        clienttranslate('nation\'s power')
      );
    }
  }

  public function getFlowPath($droplet, $USAPowerHouses = [], $dropletsInBasinMapping = null)
  {
    $USABonusEnergy = 0;
    $location = $droplet['location'];
    $path = [$location];
    $blocked = false;
    $rivers = self::getRivers();

    // if the droplet starts at a dam, we leave it (could have been added by XO power)
    $structure = self::getBuiltStructure($location);
    if (!is_null($structure) && in_array($structure['type'], [BASE, ELEVATION])) {
      return [[], 0];
    }

    do {
      // Search for the next location
      if ($location[0] == 'P') {
        $location = explode('_', $location)[0];
      }

      $basin = $rivers[$location] ?? null;
      if (\is_null($basin)) {
        throw new \BgaVisibleSystemException('Unknown route for droplet. Should not happen.' . $location . \var_export($path,true));
      }

      // Move the droplet to that location
      $location = $basin;
      $path[] = $basin;
      if (in_array(explode('_', $basin)[0], $USAPowerHouses)) {
        $USABonusEnergy++;
      }

      // If location is EXIT or Droplet is blocked by dam, stop here
      $nDroplets = is_null($dropletsInBasinMapping) // Useful for emulating water flowing
        ? self::countDropletsInBasin($basin)
        : $dropletsInBasinMapping[$basin] ?? 0;

      if (in_array($basin, Map::getExits()) || $nDroplets < self::getBasinCapacity($basin)) {
        $blocked = true;
      }
    } while (!$blocked);

    return [$path, $USABonusEnergy];
  }

  // Return all the locations that would get fed by a water droplet flowing from $location
  public function getFedLocations($location)
  {
    $droplet = [
      'location' => $location,
    ];
    list($path, $e) = self::getFlowPath($droplet);
    return $path;
  }

  // Emulate water flowing
  public function emulateFlowDroplets($additionalDroplets = [], $onlyAdditionalDroplets = false)
  {
    // Store current status of droplets
    $droplets = [];
    $currentStatus = [];
    foreach (self::$infos as $spaceId => $info) {
      if (isset($info['droplets'])) {
        $currentStatus[$spaceId] = count($info['droplets']);
        if (!$onlyAdditionalDroplets) {
          foreach ($info['droplets'] as $d) {
            $droplets[] = $d;
          }
        }
      }
    }

    // Add virutal droplets
    foreach ($additionalDroplets as $location) {
      $currentStatus[$location] = ($currentStatus[$location] ?? 0) + 1;
      $droplets[] = [
        'location' => $location,
      ];
    }

    // Now move all the droplets
    $passingDroplets = [];
    foreach ($droplets as $droplet) {
      list($path, $energy) = self::getFlowPath($droplet, [], $currentStatus);
      if (count($path) == 0) {
        continue;
      }
      // Remove drop from initial location
      $location = $droplet['location'];
      $currentStatus[$location]--;

      // Log the droplet flowing along location
      foreach ($path as $l) {
        $passingDroplets[$l][] = $location;
      }

      // Add it to final location if not EXIT
      $location = $path[count($path) - 1];
      if (!in_array($location, Map::getExits())) {
        $currentStatus[$location] = ($currentStatus[$location] ?? 0) + 1;
      }
    }

    return [$currentStatus, $passingDroplets];
  }

  /////////////////////////////////////////////////////////////
  //  ____                _            _   _
  // |  _ \ _ __ ___   __| |_   _  ___| |_(_) ___  _ __
  // | |_) | '__/ _ \ / _` | | | |/ __| __| |/ _ \| '_ \
  // |  __/| | | (_) | (_| | |_| | (__| |_| | (_) | | | |
  // |_|   |_|  \___/ \__,_|\__,_|\___|\__|_|\___/|_| |_|
  //
  /////////////////////////////////////////////////////////////
  public function getProductionSystems(
    $company,
    $bonus,
    $constraints = null,
    $objTileComputation = false,
    $germanPower = false
  ) {
    $credits = $company->countReserveResource(CREDIT);
    $systems = [];
    foreach (self::getZones() as $zoneId => $zone) {
      // Compute the possible conduits
      $conduits = [];
      foreach ($zone['conduits'] ?? [] as $sId => $conduit) {
        // Is this conduit built by someone ?
        $meeple = self::getBuiltStructure($sId, $objTileComputation ? $company : null);
        if (is_null($meeple)) {
          continue;
        }

        // Is it linked to a powerhouse built by the company ?
        $powerhouse = self::getLinkedPowerhouse($sId, $company);
        if (is_null($powerhouse) || $powerhouse['location'] == $constraints) {
          continue;
        }

        $conduits[$sId] = [
          'conduitOwnerId' => $meeple['cId'],
          'conduitSpaceId' => $sId,
          'powerhouseSpaceId' => $powerhouse['location'],
          'conduitProduction' => $conduit['production'],
        ];
      }
      if (empty($conduits)) {
        continue;
      }

      // Compute the possible dams
      $dams = [];
      foreach ($zone['basins'] ?? [] as $basin) {
        $owners = $objTileComputation ? [$company] : [COMPANY_NEUTRAL, $company];
        $dam = self::getBuiltStructure($basin, $owners);
        if (is_null($dam)) {
          continue;
        }
        $nDroplets = self::countDropletsInBasin($basin);
        if (!$objTileComputation && $nDroplets == 0) {
          continue;
        }

        $dams[] = [
          'basin' => $basin,
          'droplets' => $nDroplets,
        ];
      }

      // Take all the pair to have corresponding systems for that zone
      foreach ($dams as $dam) {
        foreach ($conduits as $conduit) {
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
            if ($company->isXO(\XO_VIKTOR) && !$germanPower) {
              $energy = max($energy, 4);
            }
            $energy += $bonus;
            if (!$germanPower) {
              $energy += $company->getProductionBonus();
            }

            if ($energy > 0) {
              $system['productions'][$i] = $energy;
            }
          }

          // Add it to the list if at least one possible > 0 production
          if (!empty($system['productions']) || $objTileComputation) {
            $systems[] = $system;
            if ($objTileComputation) {
              break; // Only count 1 max connecte conduit per base for $objTileComputation
            }
          }
        }
      }
    }

    return $systems;
  }

  // AUTOMA
  // Get space ids of slots allowing to get a complete production system
  public function getAlmostCompleteProductionSystems($company, $structure)
  {
    $systems = [];
    foreach (self::getZones() as $zoneId => $zone) {
      // Compute the possible conduits
      $conduits = [];
      foreach ($zone['conduits'] ?? [] as $sId => $conduit) {
        // Is this conduit built by someone ?
        $meeple = self::getBuiltStructure($sId);
        if (is_null($meeple) && $structure != CONDUIT) {
          continue;
        }

        // Is it linked to a powerhouse built by the company ?
        $powerhouse = self::getLinkedPowerhouse($sId, $company);
        $powerhouseSpaceId = null;
        if (is_null($powerhouse)){
          if($structure != POWERHOUSE) {
            continue;
          } else {
            foreach(self::getLinkedPowerhousesSpaces($sId) as $pId){
              if (is_null(Map::getBuiltStructure($pId))) {
                $powerhouseSpaceId = $pId;
                break;
              }
            }

            if(is_null($powerhouseSpaceId)){
              continue;
            }
          }
        } else {
          $powerhouseSpaceId = $powerhouse['location'];
        }

        $conduits[$sId] = [
          'conduitSpaceId' => $sId,
          'powerhouseSpaceId' => $powerhouseSpaceId,
        ];
      }

      // Compute the possible dams
      $dams = [];
      foreach ($zone['basins'] ?? [] as $basin) {
        $dam = self::getBuiltStructure($basin, [\COMPANY_NEUTRAL, $company]);
        if (is_null($dam) && $structure != BASE) {
          continue;
        }

        $dams[] = [
          'basin' => $basin,
        ];
      }

      // Take all the pair to have corresponding systems for that zone
      foreach ($dams as $dam) {
        foreach ($conduits as $conduit) {
          $system = array_merge($dam, $conduit);
          $systems[] = $system;
        }
      }
    }

    return $systems;
  }
}
