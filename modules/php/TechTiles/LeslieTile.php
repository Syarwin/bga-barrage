<?php
namespace BRG\TechTiles;

use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Core\Globals;

/*
 * Leslie Tile: Leslie XO special tile
 */

class LeslieTile extends \BRG\TechTiles\BasicTile
{
  public function getDescs()
  {
    return [
      clienttranslate(
        'When you perform an External Works action, you can place the required Machineries on your Construction Wheel together with this tile, as if you were performing a construction action. Then, rotate the Wheel by one segment.'
      ),
      clienttranslate(
        "The Machineries and the tile will be available again when the Construction Wheel has made a complete rotation. You don't have to place Engineers in a construction action space on your Company board, but you still have to place them on the connected External Works action space."
      ),
      clienttranslate(
        'You cannot use this special ability if the special Technology tile is already on your Construction Wheel.'
      ),
    ];
  }

  // Can copy another tech tile placed on the wheel.
  public function canConstruct($structure)
  {
    return false;
  }
}
