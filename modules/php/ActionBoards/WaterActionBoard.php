<?php
namespace BRG\ActionBoards;

use BRG\Managers\Meeples;
use BRG\Managers\Companies;
use BRG\Core\Notifications;
use BRG\Core\Engine;
use BRG\Core\Globals;

/*
 * Water Management action space board
 */

class WaterActionBoard extends AbstractActionBoard
{
  protected static $id = BOARD_WATER;
  public static function getName()
  {
    return clienttranslate('Water Management');
  }

  public function getUiStructure()
  {
    $rows = [];
    $rows[] = ['p2', '<WATER:2>', 'p2c'];
    $rows[] = ['d1', '<WATER_DOWN:1>', 'd1c'];
    return $rows;
  }

  public function getAvailableSpaces()
  {
    $spaces = [];

    $spaces[] = [
      'board' => self::$id,
      'uid' => self::$id . '-p2',
      'nEngineers' => 1,
      'tooltip' => clienttranslate('Place 2 water drops'),
      'flow' => [
        'action' => PLACE_DROPLET,
        'args' => [
          'n' => 2,
          'flows' => false,
        ],
      ],
    ];

    $spaces[] = [
      'board' => self::$id,
      'uid' => self::$id . '-d1',
      'nEngineers' => 1,
      'tooltip' => clienttranslate('Place 1 water drop and let it flow'),
      'flow' => [
        'action' => PLACE_DROPLET,
        'args' => [
          'n' => 1,
          'flows' => true,
        ],
      ],
    ];

    // Add the costy action space
    foreach ($spaces as $space) {
      $space['uid'] .= 'c';
      $space['nEngineers'] = 2;
      $space['cost'] = 3;
      $spaces[] = $space;
    }

    return $spaces;
  }
}
