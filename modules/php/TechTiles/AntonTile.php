<?php
namespace BRG\TechTiles;

use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Core\Globals;

/*
 * Anton Tile: Anton XO special tile
 */

class AntonTile extends \BRG\TechTiles\BasicTile
{
  // Can copy another tech tile placed on the wheel.
  public function canConstruct($structure)
  {
    if (Globals::getAntonPower() == '') {
      foreach ($this->getWheelTiles() as $tile) {
        if ($tile->canConstruct($structure)) {
          return true;
        }
      }
      return false;
    } else {
      return TechnologyTiles::get(Globals::getAntonPower())->canConstruct($structure);
    }
  }

  protected function getWheelTiles()
  {
    return TechnologyTiles::getFilteredQuery(Companies::getActive()->getId(), 'wheel')->get();
  }

  public function getPowerFlow($slot)
  {
    // anytime power triggered
    if (Globals::getAntonPower() == '') {
      $flow = ['type' => NODE_XOR, 'childs' => []];
      foreach ($this->getWheelTiles() as $tile) {
        if ($tile->isAnyTime()) {
          $f = $tile->getPowerFlow($slot);
          if (is_null($f)) {
            continue;
          }
          $f['description'] = $tile->getAnyTimeDesc();
          $f['args']['tileId'] = $this->id;
          $flow['childs'][] = [
            'type' => NODE_SEQ,
            'childs' => [
              [
                'action' => \SPECIAL_EFFECT,
                'args' => ['tileId' => $this->id, 'method' => 'activate', 'args' => [$tile->getId()]],
              ],
              $f,
            ],
          ];
        }
      }
      return $flow;
    } else {
      return TechnologyTiles::get(Globals::getAntonPower())->getPowerFlow($slot) ?? null;
    }
  }

  public function isAnyTime()
  {
    foreach ($this->getWheelTiles() as $tile) {
      if ($tile->getType() == \ANTON_TILE) {
        continue;
      }
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
    if (Globals::getAntonPower() == '') {
      return parent::getCostModifier($costs, $slot, $machine, $n);
    }
    return TechnologyTiles::get(Globals::getAntonPower())->getCostModifier($costs, $slot, $machine, $n);
  }

  public function getUnitsModifier($n)
  {
    if (Globals::getAntonPower() == '') {
      return parent::getUnitsModifier($n);
    }
    return TechnologyTiles::get(Globals::getAntonPower())->getUnitsModifier($n);
  }

  public function engineersNeeded()
  {
    if (Globals::getAntonPower() == '') {
      return parent::engineersNeeded();
    }
    return TechnologyTiles::get(Globals::getAntonPower())->engineersNeeded();
  }

  public function isAutomatic()
  {
    if (Globals::getAntonPower() == '') {
      return parent::isAutomatic();
    }
    return TechnologyTiles::get(Globals::getAntonPower())->isAutomatic();
  }

  public function ignoreCostMalus()
  {
    if (Globals::getAntonPower() == '') {
      return parent::ignoreCostMalus();
    }
    return TechnologyTiles::get(Globals::getAntonPower())->ignoreCostMalus();
  }

  public function activate($tile)
  {
    Globals::setAntonPower($tile);
  }
}
