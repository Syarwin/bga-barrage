<?php
namespace BRG\TechTiles;

use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Meeples;
use BRG\Map;

/*
 * Level 2 powerhouse
 */

class L2Powerhouse extends \BRG\TechTiles\BasicTile
{
  public function canConstruct($structure)
  {
    return $structure == \POWERHOUSE;
  }

  public function getPowerFlow($slot)
  {
    //TODO
  }
}
