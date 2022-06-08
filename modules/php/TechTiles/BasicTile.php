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

  protected $staticAttributes = ['engineersNeeded', 'automatic', 'ignoreCostMalus'];
  protected $engineersNeeded = true;
  protected $automatic = true;
  protected $ignoreCostMalus = false;

  public function canConstruct($structure)
  {
    return $this->type == JOKER || $this->type == $structure;
  }

  /*************** ANYTIME management **************/
  public function isAnyTime()
  {
    return false;
  }

  public function getAnyTimeDesc()
  {
    return '';
  }

  /**************** Tile Power **************/
  public function getPowerFlow($slot)
  {
    return null;
  }

  public function getCostModifier($costs, $slot, $machine, $n)
  {
    return $costs;
  }

  public function getUnitsModifier($n)
  {
    return $n;
  }

  public function engineersNeeded()
  {
    return $this->engineersNeeded;
  }

  public function isAutomatic()
  {
    return $this->automatic;
  }

  public function ignoreCostMalus()
  {
    return $this->ignoreCostMalus;
  }
}
