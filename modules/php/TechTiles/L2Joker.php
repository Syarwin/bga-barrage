<?php
namespace BRG\TechTiles;

use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Meeples;
use BRG\Map;
use BRG\Helpers\Utils;

/*
 * Level 2 joker
 */

class L2Joker extends \BRG\TechTiles\BasicTile
{
  public function canConstruct($structure)
  {
    return true;
  }

  public function getCostModifier($costs, $slot, $machine, $n)
  {
    if ($slot['type'] == CONDUIT) {
      Utils::addCost($costs, [MIXER => 1, \EXCAVATOR => 1, 'nb' => 1]);
      Utils::addCost($costs, [MIXER => 2, 'nb' => 1]);
    } else {
      Utils::addCost($costs, [MIXER => 1, 'nb' => 1]);
      Utils::addCost($costs, [EXCAVATOR => 1, 'nb' => 1]);
    }

    return $costs;
  }
}
