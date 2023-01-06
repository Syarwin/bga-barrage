<?php
namespace BRG\TechTiles;
use BRG\Managers\Buildings;

/*
 * Level 1 building advanced tile
 */

class L1Building extends AdvancedTile
{
  protected $structureType = BUILDING;
  protected $lvl = 1;
  public function getDescs()
  {
    $descs = parent::getDescs();
    $descs[] = clienttranslate(
      'When you use this tile, you can immediately perform the action of the Private Building tile you have just activated. In order to do so, you don’t need to place any Engineers in the Private Building action space. You must still pay any cost illustrated in the action symbol.'
    );
    return $descs;
  }

  public function getPowerFlow($slot)
  {
    $id = (int) explode('-', $slot['id'])[1];
    $building = Buildings::getSingle($id);
    $flow = $building->getFlow();
    $flow['optional'] = true;
    return $flow;
  }
}
