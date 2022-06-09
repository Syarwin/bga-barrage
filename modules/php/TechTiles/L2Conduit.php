<?php
namespace BRG\TechTiles;

use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Meeples;
use BRG\Map;
use BRG\Helpers\Utils;

/*
 * Level 2 conduit
 */

class L2Conduit extends \BRG\TechTiles\BasicTile
{
  public function canConstruct($structure)
  {
    return $structure == \CONDUIT;
  }

  public function getUnitsModifier($n)
  {
    if ($n > 5) {
      return 5;
    } else {
      return $n;
    }
  }

  public function getCostModifier($costs, $slot, $machine, $n)
  {
    foreach ($costs['trades'] as &$trade) {
      if (isset($trade[EXCAVATOR])) {
        $trade[EXCAVATOR] = 1;
      }
    }
    return $costs;
  }
}
