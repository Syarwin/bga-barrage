<?php
namespace BRG\ActionBoards;

use BRG\Managers\Meeples;
use BRG\Managers\Companies;
use BRG\Core\Notifications;
use BRG\Core\Engine;
use BRG\Core\Globals;

/*
 * Workshop action space board
 */

class WorkshopActionBoard extends AbstractActionBoard
{
  protected static $id = \BOARD_WORSKHOP;
  public static function getName()
  {
    return clienttranslate('Workshop');
  }

  public function getUiStructure()
  {
    $rows = [];

    $rows[] = [
      'r1',
      [
        'i' => '<ROTATE:1>',
        't' => clienttranslate('Rotate your construction wheel by one segment'),
      ],
      'r1bis',
    ];

    if (Companies::count() >= 3) {
      $rows[] = [
        'r2',
        [
          'i' => '<CREDIT:2><ARROW><ROTATE:2>',
          't' => clienttranslate('Pay two credits to rotate your construction wheel by two segments'),
        ],
        'r2c',
      ];
    }

    $rows[] = [
      'r3',
      [
        'i' => '<CREDIT:5><ARROW><ROTATE:3>',
        't' => clienttranslate('Pay five credits to rotate your construction wheel by three segments'),
      ],
      'r3c',
    ];

    return $rows;
  }

  public function getAvailableSpaces()
  {
    $spaces = [];

    if (Companies::count() >= 3) {
      $spaces[] = [
        'board' => self::$id,
        'uid' => self::$id . '-r2',
        'nEngineers' => 2,
        'flow' => [
          'action' => ROTATE_WHEEL,
          'args' => [
            'n' => 2,
            'cost' => 2,
          ],
        ],
      ];
    }

    $spaces[] = [
      'board' => self::$id,
      'uid' => self::$id . '-r3',
      'nEngineers' => 2,
      'flow' => [
        'action' => ROTATE_WHEEL,
        'args' => [
          'n' => 3,
          'cost' => 5,
        ],
      ],
    ];

    // Add the costy action space
    foreach ($spaces as $space) {
      $space['uid'] .= 'c';
      $space['nEngineers'] = 3;
      $space['cost'] = 3;
      $spaces[] = $space;
    }

    $spaces[] = [
      'board' => self::$id,
      'uid' => self::$id . '-r1',
      'nEngineers' => 1,
      'flow' => [
        'action' => ROTATE_WHEEL,
        'args' => [
          'n' => 1,
        ],
      ],
    ];

    $spaces[] = [
      'board' => self::$id,
      'uid' => self::$id . '-r1bis',
      'nEngineers' => 2,
      'flow' => [
        'action' => ROTATE_WHEEL,
        'args' => [
          'n' => 1,
        ],
      ],
    ];

    return $spaces;
  }
}
