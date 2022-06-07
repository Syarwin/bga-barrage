<?php
namespace BRG\TechTiles;

use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Meeples;
use BRG\Map;

/*
 * Level 3 elevation
 */

class L3Elevation extends \BRG\TechTiles\BasicTile
{
  public function canConstruct($structure)
  {
    return $structure == \ELEVATION;
  }

  public function getPowerFlow($slot)
  {
    //TODO
  }
}
