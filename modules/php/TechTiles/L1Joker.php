<?php
namespace BRG\TechTiles;

/*
 * Level 1 joker
 */

class L1Joker extends AdvancedTile
{
  protected $ignoreCostMalus = true;
  protected $structureType = JOKER;
  public function getDescs()
  {
    $descs = parent::getDescs();
    $descs[] = clienttranslate(
      'When you use this tile to build a structure, you do not have to pay 3 credits if you place the structure in a building space with a red bordered icon.'
    );
    $descs[] = clienttranslate(
      '(If you placed your Engineers in the red-bordered action space, you still have to pay those 3 Credits.)'
    );
    return $descs;
  }
}
