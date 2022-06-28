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
  public function getDescs()
  {
    return [
      clienttranslate(
        'When you use this Technology tile you can copy another Technology tile of your choice on your Construction Wheel. This special tile copies both the main building effect and the special effect of the copied tile.'
      ),
    ];
  }

  public function activate($tile)
  {
    Globals::setAntonPower($tile);
  }

  protected function getWheelTiles()
  {
    return TechnologyTiles::getFilteredQuery(Companies::getActive()->getId(), 'wheel')
      ->get()
      ->filter(function ($tile) {
        return $tile->getType() != ANTON_TILE;
      });
  }

  protected function getCopiedTile()
  {
    return Globals::getAntonPower() == '' ? null : TechnologyTiles::get(Globals::getAntonPower());
  }

  // Can copy another tech tile placed on the wheel.
  public function canConstruct($structure)
  {
    $tile = $this->getCopiedTile();
    if (!is_null($tile)) {
      return $tile->canConstruct($structure);
    }

    foreach ($this->getWheelTiles() as $tile) {
      if ($tile->canConstruct($structure)) {
        return true;
      }
    }
    return false;
  }

  public function getPowerFlow($slot)
  {
    $tile = $this->getCopiedTile();
    if (!is_null($tile)) {
      return $tile->getPowerFlow($slot) ?? null;
    }

    // anytime power triggered
    $flow = ['type' => NODE_XOR, 'childs' => []];
    foreach ($this->getWheelTiles() as $tile) {
      if ($tile->isAlternativeAction()) {
        $f = $tile->getPowerFlow($slot);
        if (is_null($f)) {
          continue;
        }
        $f['description'] = $tile->getAlternativeActionDesc();
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
  }

  public function isAlternativeAction()
  {
    foreach ($this->getWheelTiles() as $tile) {
      if ($tile->isAlternativeAction()) {
        return true;
      }
    }

    return false;
  }

  public function getAlternativeActionDesc()
  {
    return clienttranslate('Use Anton\'s power');
  }

  public function getCostModifier($costs, $slot, $machine, $n)
  {
    $tile = $this->getCopiedTile();
    return is_null($tile)
      ? parent::getCostModifier($costs, $slot, $machine, $n)
      : $tile->getCostModifier($costs, $slot, $machine, $n);
  }

  public function getUnitsModifier($n)
  {
    $tile = $this->getCopiedTile();
    return is_null($tile) ? parent::getUnitsModifier($n) : $tile->getUnitsModifier($n);
  }

  public function isAutomatic()
  {
    $tile = $this->getCopiedTile();
    return is_null($tile) ? parent::isAutomatic() : $tile->isAutomatic();
  }

  public function ignoreCostMalus()
  {
    $tile = $this->getCopiedTile();
    return is_null($tile) ? parent::ignoreCostMalus() : $tile->ignoreCostMalus();
  }
}
