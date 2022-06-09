<?php
namespace BRG\TechTiles;

use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;

/*
 * Anton Tile: Anton XO special tile
 */

class AntonTile extends \BRG\TechTiles\BasicTile
{
  // Can copy another tech tile placed on the wheel.
  public function canConstruct($structure)
  {
    foreach ($this->getWheelTiles() as $tile) {
      if ($tile->canConstruct($structure)) {
        return true;
      }
    }
    return false;
  }

  protected function getWheelTiles()
  {
    return TechnologyTiles::getFilteredQuery($this->cId, 'wheel')->get();
  }

  public function getPowerFlow($slot)
  {
    // anytime power triggered
    if (Globals::getAntonPower() == '') {
      $flow = ['type' => NODE_XOR, 'childs' => []];
      foreach ($this->getWheelTiles() as $tile) {
        if ($tile->isAnyTime()) {
          $flow['childs'] = $tile->getPowerFlow($slot);
        }
      }
      return $flow;
    } else {
      return TechnologyTiles::get(Globals::getAntonPower())->getPowerFlow($slot);
    }
  }

  public function isAnyTime()
  {
    foreach ($this->getWheelTiles() as $tile) {
      if ($tile->isAnyTime()) {
        return true;
      }
    }
    return false;
  }

  public function getAnyTimeDesc()
  {
    return clienttranslate('Use Anton\'s power');
  }

  public function getCostModifier($costs, $slot, $machine, $n)
  {
    return TechnologyTiles::get(Globals::getAntonPower())->getCostModifier($costs, $slot, $machine, $n);
  }

  public function getUnitsModifier($n)
  {
    return TechnologyTiles::get(Globals::getAntonPower())->getUnitModifier($n);
  }

  public function engineersNeeded()
  {
    return TechnologyTiles::get(Globals::getAntonPower())->engineersNeeded();
  }

  public function isAutomatic()
  {
    return TechnologyTiles::get(Globals::getAntonPower())->isAutomatic();
  }

  public function ignoreCostMalus()
  {
    return TechnologyTiles::get(Globals::getAntonPower())->isAutomatic();
  }
}
