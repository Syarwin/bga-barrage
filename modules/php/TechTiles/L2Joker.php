<?php
namespace BRG\TechTiles;

use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Meeples;
use BRG\Map;

/*
 * Level 2 joker
 */

class L2Joker extends \BRG\TechTiles\BasicTile
{
  public function canConstruct($structure)
  {
    return true;
  }

  public function getPowerFlow($slot)
  {
    //TODO
  }
}
