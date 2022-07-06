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

    $spaces[] = [
      'board' => self::$id,
      'uid' => self::$id . '-d1',
      'nEngineers' => 1,
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
