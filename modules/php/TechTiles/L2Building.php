<?php
namespace BRG\TechTiles;
use BRG\Map;

/*
 * Level 2 building advanced tile
 */

class L2Building extends AdvancedTile
{
  protected $structureType = BUILDING;
  protected $lvl = 2;
  public function getDescs()
  {
    $descs = parent::getDescs();
    $descs[] = clienttranslate(
      'When you use this tile, put in your Construction Wheel only one of the two types of Machinery required by the construction cost of the Private Building you are activating. If the construction cost is only of one type of Machinery, you can build it for free.'
    );
    return $descs;
  }

  public function applyConstructCostModifier(&$costs, $slot)
  {
    $cost = $costs['costs']['fees'][0];
    if (isset($cost[\EXCAVATOR])) {
      $nc = [MIXER => $cost[MIXER] ?? 0];
      $costs['costs']['fees'][] = $nc;
    }
    if (isset($cost[MIXER])) {
      $nc = [\EXCAVATOR => $cost[\EXCAVATOR] ?? 0];
      $costs['costs']['fees'][] = $nc;
    }
  }
}
