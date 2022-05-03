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
    'structure' => 'structure',
  ];
}