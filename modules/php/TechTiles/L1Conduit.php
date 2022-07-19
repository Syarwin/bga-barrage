<?php
namespace BRG\TechTiles;

/*
 * Level 1 conduit
 */

class L1Conduit extends AdvancedTile
{
  protected $structureType = CONDUIT;
  public function getDescs()
  {
    $descs = parent::getDescs();
    $descs[] = clienttranslate(
      'When you use this tile, gain a number of Credits equal to the production value of the Conduit you have just built, multiplied by 2.'
    );
    return $descs;
  }

  public function getPowerFlow($slot)
  {
    return [
      'action' => GAIN,
      'args' => [CREDIT => $slot['production'] * 2],
      'source' => clienttranslate('(Advanced tile\'s effect)'),
    ];
  }
}
