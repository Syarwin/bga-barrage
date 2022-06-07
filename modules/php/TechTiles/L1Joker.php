<?php
namespace BRG\TechTiles;

use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Meeples;
use BRG\Map;

/*
 * Level 1 joker
 */

class L1Joker extends \BRG\TechTiles\BasicTile
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->ignoreCostMalus = true;
  }

  public function canConstruct($structure)
  {
    return true;
  }
}
