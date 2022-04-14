<?php
namespace BRG;

class Map
{
  protected static $map = null;
  public static function init($mapId)
  {
    $className = 'BRG\Maps\\'.$mapId;
    static::$map = new $className();
  }

  public static function getUiData()
  {
    return [
      'conduits' => self::$map->getConduits(),
    ];
  }
}
