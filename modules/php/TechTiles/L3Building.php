<?php
namespace BRG\TechTiles;
use BRG\Map;

/*
 * Level 3 building advanced tile
 */

class L3Building extends AdvancedTile
{
  protected $structureType = BUILDING;
  protected $lvl = 3;
  public function getDescs()
  {
    $descs = parent::getDescs();
    $descs[] = clienttranslate(
      'When you use this tile, immediately score the Victory Points awarded by the Private Building you have just activated. You will score them again at the end of the game, as usual.'
    );
    return $descs;
  }
}
