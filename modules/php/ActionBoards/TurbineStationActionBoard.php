<?php
namespace BRG\ActionBoards;

use BRG\Managers\Meeples;
use BRG\Managers\Companies;
use BRG\Core\Notifications;
use BRG\Core\Engine;
use BRG\Core\Globals;

/*
 * Turbine Station action space board
 */

class TurbineStationActionBoard extends AbstractActionBoard
{
  protected static $id = BOARD_TURBINE;
  public static function getName()
  {
    return clienttranslate('Turbine Station');
  }

  public function getUiStructure()
  {
    $rows = [];

    $rows[] = ['b2', ['i' => '<PRODUCTION>[+2]', 't' => clienttranslate('Produce with +2 bonus')], 'b2c'];

    if (Companies::count() >= 4) {
      $rows[] = ['b1', ['i' => '<PRODUCTION>[+1]', 't' => clienttranslate('Produce with +1 bonus')], 'b1c'];
    }

    $rows[] = ['0', ['i' => '<PRODUCTION>', 't' => clienttranslate('Produce energy')], '0c'];

    if (Companies::count() >= 3) {
      $rows[] = ['m1', ['i' => '<PRODUCTION>[-1]', 't' => clienttranslate('Produce with -1 bonus')], 'm1c'];
    }

    $rows[] = ['m2', ['i' => '<PRODUCTION>[-2]', 't' => clienttranslate('Produce with -2 bonus')], 'm2bis'];

    return $rows;
  }

  public function getAvailableSpaces()
  {
    $spaces = [];

    $spaces[] = [
      'board' => self::$id,
      'uid' => self::$id . '-b2',
      'nEngineers' => 2,
      'flow' => [
        'action' => PRODUCE,
        'args' => [
          'bonus' => 2,
        ],
      ],
    ];

    if (Companies::count() >= 4) {
      $spaces[] = [
        'board' => self::$id,
        'uid' => self::$id . '-b1',
        'nEngineers' => 2,
        'flow' => [
          'action' => PRODUCE,
          'args' => [
            'bonus' => 1,
          ],
        ],
      ];
    }

    $spaces[] = [
      'board' => self::$id,
      'uid' => self::$id . '-0',
      'nEngineers' => 2,
      'flow' => [
        'action' => PRODUCE,
      ],
    ];

    if (Companies::count() >= 3) {
      $spaces[] = [
        'board' => self::$id,
        'uid' => self::$id . '-m1',
        'nEngineers' => 2,
        'flow' => [
          'action' => PRODUCE,
          'args' => [
            'bonus' => -1,
          ],
        ],
      ];
    }

    // Add the costy action space
    foreach ($spaces as $space) {
      $space['uid'] .= 'c';
      $space['nEngineers'] = 3;
      $space['cost'] = 3;
      $spaces[] = $space;
    }

    $spaces[] = [
      'board' => self::$id,
      'uid' => self::$id . '-m2',
      'nEngineers' => 1,
      'flow' => [
        'action' => PRODUCE,
        'args' => [
          'bonus' => -2,
        ],
      ],
    ];

    $spaces[] = [
      'board' => self::$id,
      'uid' => self::$id . '-m2bis',
      'nEngineers' => 2,
      'flow' => [
        'action' => PRODUCE,
        'args' => [
          'bonus' => -2,
        ],
      ],
    ];

    return $spaces;
  }
}
