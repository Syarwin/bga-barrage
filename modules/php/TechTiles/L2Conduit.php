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

class L2Conduit extends AdvancedTile
{
  protected $structureType = CONDUIT;
  public function getDescs()
  {
    $descs = parent::getDescs();
    $descs[] = clienttranslate(
      'When you use this tile to build a Conduit which costs 6 or more Excavators (for its production is 3 or more), its cost becomes 5 Excavators. (If the cost is 5 or less, it remains the same.)'
    );
    return $descs;
  }


  public function getCostModifier($costs, $slot, $machine, $n)
  {
    $costs['trades'][] = [
      EXCAVATOR => 5,
      'nb' => $n,
    ];

    return $costs;
  }
}
