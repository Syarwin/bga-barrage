<?php
namespace BRG\TechTiles;

/*
 * Advanced Tile: all utility functions concerning a tech tile
 */

class AdvancedTile extends \BRG\TechTiles\BasicTile
{
  protected $structureType = null;
  public function getStructureType()
  {
    return $this->structureType;
  }
}
