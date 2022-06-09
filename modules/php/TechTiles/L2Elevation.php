<?php
namespace BRG\TechTiles;

use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Meeples;
use BRG\Map;

/*
 * Level 2 elevation
 */

class L2Elevation extends \BRG\TechTiles\BasicTile
{
  public function canConstruct($structure)
  {
    return $structure == \ELEVATION;
  }

  public function getPowerFlow($slot)
  {
    $toCreate = Map::getBasinCapacity($slot['id']) - Map::countDropletsInBasin($slot['id']);
    if ($toCreate > 0) {
      $dropletsToAdd = [];
      $dropletsToAdd[] = ['location' => $slot['id'], 'nb' => $toCreate];
      return ['action' => PLACE_DROPLET, 'args' => ['autoDroplet' => $dropletsToAdd]];
    }
    return null;
  }
}
