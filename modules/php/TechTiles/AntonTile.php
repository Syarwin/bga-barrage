<?php
namespace BRG\TechTiles;

use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;

/*
 * Anton Tile: all utility functions concerning a tech tile
 */

class AntonTile extends \BRG\TechTiles\BasicTile
{
  // Can copy another tech tile placed on the wheel.
  public function canConstruct($structure)
  {
    $tiles = TechnologyTiles::getFilteredQuery($this->cId, 'wheel')->get();
    foreach ($tiles as $tile) {
      if ($tile->canConstruct($structure)) {
        return true;
      }
    }
    return false;
  }

  public function getPowerFlow($slot)
  {
    return TechnologyTiles::get(Globals::getAntonPower())->getPowerFlow($slot);
  }

  // TODO: add cost modifier and other effects
}
