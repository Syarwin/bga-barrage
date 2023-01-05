<?php
namespace BRG\TechTiles;
use BRG\Map;

/*
 * Level 1 base advanced tile
 */

class L1Base extends AdvancedTile
{
  protected $structureType = BASE;
  protected $lvl = 1;
  public function getDescs()
  {
    $descs = parent::getDescs();
    $descs[] = clienttranslate(
      'When you use this tile, place a Water Drop in all your empty Dams. Do not put Water Drops in Dams which already have at least one Water Drop. Do not put Water Drops in Neutral Dams.'
    );
    return $descs;
  }

  public function getPowerFlow($slot)
  {
    $company = $this->getCompany();
    $dropletsToAdd = [];
    foreach ($company->getBuiltStructures(BASE) as $id => $dam) {
      if (Map::countDropletsInBasin($dam['location']) == 0) {
        $dropletsToAdd[] = ['location' => $dam['location'], 'nb' => 1];
      }
    }

    if (count($dropletsToAdd) > 0) {
      return ['action' => PLACE_DROPLET, 'optional' => true, 'args' => ['autoDroplet' => $dropletsToAdd]];
    }
  }
}
