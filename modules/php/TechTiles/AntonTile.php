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
    } else {
      return false;
    }
  }

  public function addAlternativeActions(&$actions)
  {
    foreach ($this->getWheelTiles() as $tile) {
      if ($tile->isAlternativeAction()) {
        $fakeActions = [];
        $tile->addAlternativeActions($fakeActions);
        foreach ($fakeActions as $action) {
          $actions[] = [
            'desc' => [
              'log' => clienttranslate('Use Anton to ${action}'),
              'args' => [
                'i18n' => ['action'],
                'action' => $action['desc'],
              ],
            ],
            'flow' => [
              'type' => NODE_SEQ,
              'childs' => [
                [
                  'action' => \SPECIAL_EFFECT,
                  'args' => ['tileId' => $this->id, 'method' => 'activate', 'args' => [$tile->getId()]],
                ],
                $action['flow'],
              ],
            ],
          ];
        }
      }
    }
  }

  public function getPowerFlow($slot)
  {
    $tile = $this->getCopiedTile();
    return is_null($tile) ? null : $tile->getPowerFlow($slot);
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

  public function applyConstructCostModifier(&$costs, $slot)
  {
    $tile = $this->getCopiedTile();
    if (!is_null($tile)) {
      $tile->applyConstructCostModifier($costs, $slot);
    }
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
