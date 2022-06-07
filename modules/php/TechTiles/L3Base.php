<?php
namespace BRG\TechTiles;

use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Meeples;
use BRG\Map;

/*
 * Level 3 base
 */

class L3Base extends \BRG\TechTiles\BasicTile
{
  public function canConstruct($structure)
  {
    return $structure == BASE;
  }

  public function getPowerFlow($slot)
  {
    //TODO
  }
}
