<?php
namespace BRG;
use BRG\Core\Globals;
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
      'id' => self::$map->getId(),
      'headstreams' => Globals::getHeadstreams(),
      'bonusTiles' => Globals::getBonusTiles(),
      'conduits' => self::$map->getConduits(),
      'powerhouses' => array_values(self::$map->getPowerhouses()),
      'basins' => array_values(self::$map->getBasins()),
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
}
