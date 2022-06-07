<?php
namespace BRG\TechTiles;

use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Meeples;
use BRG\Map;

/*
 * Level 2 conduit
 */

class L2Conduit extends \BRG\TechTiles\BasicTile
{
  public function canConstruct($structure)
  {
    return $structure == \CONDUIT;
  }

  public function getPowerFlow($slot)
  {
    //TODO
  }
}
