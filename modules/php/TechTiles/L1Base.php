<?php
namespace BRG\TechTiles;

use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Meeples;
use BRG\Map;

/*
 * Level 1 base advanced tile
 */

class L1Base extends \BRG\TechTiles\BasicTile
{
  public function canConstruct($structure)
  {
    return $structure == BASE;
  }

  public function getPowerFlow($slot)
  {
    $company = Companies::get($this->cId);
    $dropletsToAdd = [];
    foreach ($company->getBuiltStructures(BASE) as $id => $dam) {
      if (Map::countDropletsInBasin($dam['location']) == 0) {
        $dropletsToAdd[] = ['location' => $dam['location'], 'nb' => 1];
      }
    }

    if (count($dropletsToAdd) > 0) {
      return ['action' => PLACE_DROPLET, 'args' => ['autoDroplet' => $dropletsToAdd]];
    }
  }
}
