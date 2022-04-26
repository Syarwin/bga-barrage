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

  public static function setupNewGame()
  {
    self::init();

    // Draw random headstream tiles
    $headstreams = self::$map->getHeadstreams();
    $tiles = array_rand(array_flip([HT_1, HT_2, HT_3, HT_4, HT_5, HT_6, HT_7, HT_8]), count($headstreams));
    $t = [];
    foreach ($headstreams as $i => $hId) {
      $t[$hId] = $tiles[$i];
    }
    Globals::setHeadstreams($t);

    // Put random neutral dams
    $basins = self::$map->getBasinsByArea();
    //    var_dump($basins);
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

  public static function getUiData()
  {
    return [
      'id' => self::$map->getId(),
      'headstreams' => Globals::getHeadstreams(),
      'conduits' => self::$map->getConduits(),
      'powerhouses' => array_values(self::$map->getPowerhouses()),
      'basins' => array_values(self::$map->getBasins()),
    ];
  }
}
