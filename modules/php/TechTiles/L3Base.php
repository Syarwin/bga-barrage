<?php
namespace BRG\TechTiles;

use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Meeples;
use BRG\Map;

/*
 * Level 3 base
 */

class L3Base extends AdvancedTile
{
  protected $structureType = BASE;
  public function getDescs()
  {
    $descs = parent::getDescs();
    $descs[] = clienttranslate(
      'When you use this tile, you do not have to place Engineers in a construction action space of your Company board. In addition, you do not have to place the requested Excavators in your Construction Wheel.'
    );
    $descs[] = clienttranslate(
      '(Since you are not using Engineers to perform this action, this can be your last action of the Round even if you run out of Engineers.'
    );
    return $descs;
  }

  protected $alternativeAction = true;
  public function getAlternativeActionDesc()
  {
    return clienttranslate('Construct a <BASE> without <ENGINEER> & <EXCAVATOR>');
  }

  public function getPowerFlow($slot)
  {
    return [
      'action' => CONSTRUCT,
      'args' => ['type' => BASE, 'tileId' => $this->id],
    ];
  }


  public function applyConstructCostModifier(&$costs, $slot)
  {
    $costs['nb'] = -99;
  }
}
