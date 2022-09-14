<?php
namespace BRG\TechTiles;

/*
 * Level 1 powerhouse
 */

class L1Powerhouse extends AdvancedTile
{
  protected $structureType = POWERHOUSE;
  protected $lvl = 1;
  public function getDescs()
  {
    $descs = parent::getDescs();
    $descs[] = clienttranslate(
      'When you use this tile, you do not have to place Engineers in a construction action space of your Company board.'
    );
    $descs[] = clienttranslate(
      '(Since you are not using Engineers to perform this action, this can be your last action of the Round even if you run out of Engineers.)'
    );
    return $descs;
  }

  protected $alternativeAction = true;
  public function getAlternativeActionDesc()
  {
    return clienttranslate('Construct a <POWERHOUSE> without <ENGINEER>');
  }

  public function getPowerFlow($slot)
  {
    return [
      'action' => CONSTRUCT,
      'args' => ['type' => POWERHOUSE, 'tileId' => $this->id],
    ];
  }
}
