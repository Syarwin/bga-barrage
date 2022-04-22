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
    $rows[] = ['b2', '<PRODUCTION>[+2]', 'b2c'];
    if (Companies::count() >= 4) {
      $rows[] = ['b1', '<PRODUCTION>[+1]', 'b1c'];
    }
    $rows[] = ['0', '<PRODUCTION>', '0c'];
    if (Companies::count() >= 3) {
      $rows[] = ['m1', '<PRODUCTION>[-1]', 'm1c'];
    }
    $rows[] = ['m2', '<PRODUCTION>[-2]', 'm2bis'];
    return $rows;
  }

  public function getAvailableSpaces()
  {
    $spaces = [];

    $spaces[] = [
      'board' => self::$id,
      'uid' => self::$id . '-b2',
      'nEngineers' => 2,
      'tooltip' => clienttranslate('Product with +2 bonus'),
      'flow' => [
        'action' => PRODUCT,
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
        'tooltip' => clienttranslate('Product with +1 bonus'),
        'flow' => [
          'action' => PRODUCT,
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
      'tooltip' => clienttranslate('Product'),
      'flow' => [
        'action' => PRODUCT,
      ],
    ];

    if (Companies::count() >= 3) {
      $spaces[] = [
        'board' => self::$id,
        'uid' => self::$id . '-m1',
        'nEngineers' => 2,
        'tooltip' => clienttranslate('Product with -1 bonus'),
        'flow' => [
          'action' => PRODUCT,
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
      'tooltip' => clienttranslate('Product with -2 bonus'),
      'flow' => [
        'action' => PRODUCT,
        'args' => [
          'bonus' => -2,
        ],
      ],
    ];

    $spaces[] = [
      'board' => self::$id,
      'uid' => self::$id . '-m2bis',
      'nEngineers' => 2,
      'tooltip' => clienttranslate('Product with -2 bonus'),
      'flow' => [
        'action' => PRODUCT,
        'args' => [
          'bonus' => -2,
        ],
      ],
    ];

    return $spaces;
  }
}
