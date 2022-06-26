<?php
namespace BRG\TechTiles;

use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Meeples;
use BRG\Map;

/*
 * Level 2 elevation
 */

class L2Elevation extends AdvancedTile
{
  protected $structureType = ELEVATION;
  public function getDescs()
  {
    $descs = parent::getDescs();
    $descs[] = clienttranslate(
      'When you use this tile, place Water Drops in the Dam where you have just built this Elevation in order to fill it to its maximum capacity. Take the Water Drops directly from the general supply.'
    );
    return $descs;
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
