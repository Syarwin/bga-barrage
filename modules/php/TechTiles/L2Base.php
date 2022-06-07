<?php
namespace BRG\TechTiles;

use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Meeples;
use BRG\Map;

/*
 * Level 2 base
 */

class L2Base extends \BRG\TechTiles\BasicTile
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
