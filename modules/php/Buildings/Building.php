<?php
namespace BRG\Buildings;
use BRG\Managers\Companies;

/*
 * Building: all utility functions concerning a building tile
 */

class Building extends \BRG\Helpers\DB_Model
{
  protected $table = 'buildings';
  protected $primary = 'building_id';
  protected $attributes = [
    'id' => ['tile_id', 'int'],
    'location' => 'tile_location',
    'state' => ['tile_state', 'int'],
  ];

  protected $staticAttributes = ['cost', 'flow', 'vp'];

  protected function getFlow()
  {
    return [];
  }

  protected function getCentralIcon()
  {
    return [];
  }

  public function getUiStructure($cId = null)
  {
    $rows = [];

    $rows[] = ['p2', ['i' => '<WATER:2>', 't' => clienttranslate('Place 2 water drops')], 'p2c'];

    $rows[] = ['d1', ['i' => '<WATER_DOWN:1>', 't' => clienttranslate('Place 1 water drop and let it flow')], 'd1c'];

    return $rows;
  }

  public function getAvailableSpaces()
  {
    $spaces = [];

    $spaces[] = [
      'board' => self::$id,
      'uid' => self::$id . '-p2',
      'nEngineers' => 1,
      'flow' => [
        'action' => PLACE_DROPLET,
        'args' => [
          'n' => 2,
          'flows' => false,
        ],
      ],
    ];
  }
}
