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

  public function addAlternativeActions(&$actions)
  {
    $flow = $this->getPowerFlow(null);
    $flow['id'] = $this->getId();
    $actions[] = [
      'flow' => $flow,
      'desc' => $this->getAlternativeActionDesc(),
    ];
  }
}
