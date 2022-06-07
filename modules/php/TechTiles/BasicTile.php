<?php
namespace BRG\TechTiles;

/*
 * Basic Tile: all utility functions concerning a tech tile
 */

class BasicTile extends \BRG\Helpers\DB_Model
{
  protected $table = 'technology_tiles';
  protected $primary = 'tile_id';
  protected $attributes = [
    'id' => ['tile_id', 'int'],
    'location' => 'tile_location',
    'state' => ['tile_state', 'int'],
    'type' => 'type',
    'cId' => ['company_id', 'int'],
  ];

  protected $staticAttributes = ['engineersNeeded'];
  protected $engineersNeeded = true;

  public function canConstruct($structure)
  {
    return $this->type == JOKER || $this->type == $structure;
  }

  public function getPowerFlow($slot)
  {
    return null;
  }

  public function getCostModifier($cost)
  {
    return $cost;
  }

  public function engineersNeeded()
  {
    return $this->engineersNeeded;
  }

  public function isAutomatic()
  {
    return true;
  }
}
