<?php
namespace BRG;
use BRG\Core\Globals;

class Map
{
  protected static $map = null;
  public static function init()
  {
    $mapId = Globals::getMap();
    if($mapId == 0){
      return;
    }

    $classes = [
      \MAP_BASE => 'BaseMap'
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
    foreach($headstreams as $i => $hId){
      $t[$hId] = $tiles[$i];
    }
    Globals::setHeadstreams($t);

  }

  public static function getUiData()
  {
    return [
      'id' => self::$map->getId(),
      'headstreams' => Globals::getHeadstreams(),
      'conduits' => self::$map->getConduits(),
    ];
  }
}
