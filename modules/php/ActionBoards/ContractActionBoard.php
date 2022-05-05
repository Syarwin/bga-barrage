<?php
namespace BRG\ActionBoards;

use BRG\Managers\Meeples;
use BRG\Managers\Companies;
use BRG\Core\Notifications;
use BRG\Core\Engine;
use BRG\Core\Globals;

/*
 * Contract Management action space board
 */

class ContractActionBoard extends AbstractActionBoard
{
  protected static $id = BOARD_CONTRACT;
  public static function getName()
  {
    return clienttranslate('Contract Office');
  }

  public function getUiStructure()
  {
    $rows = [];

    $rows[] = [];

    $rows[] = [
      'o',
      ['i' => '<CONTRACT:1>', 't' => clienttranslate('Take ONE available Private Contract tile for free')],
      'oc',
    ];

    if (Companies::count() >= 4) {
      $rows[] = [
        'ob',
        ['i' => '<CONTRACT:1>', 't' => clienttranslate('Take ONE available Private Contract tile for free')],
        'obc',
      ];
    }

    $rows[] = [
      't',
      [
        'i' => '<CREDIT:1><ARROW><CONTRACT:2>',
        't' => clienttranslate('Pay 1 Credit and take TWO available Private Contract tiles'),
      ],
      'tbis',
    ];

    return $rows;
  }

  public function getAvailableSpaces()
  {
    $spaces = [];

    $spaces[] = [
      'board' => self::$id,
      'uid' => self::$id . '-o',
      'nEngineers' => 1,
      'flow' => [
        'action' => \TAKE_CONTRACT,
        'args' => [
          'n' => 1,
        ],
      ],
    ];

    if (Companies::count() >= 4) {
      $spaces[] = [
        'board' => self::$id,
        'uid' => self::$id . '-ob',
        'nEngineers' => 1,
        'flow' => [
          'action' => \TAKE_CONTRACT,
          'args' => [
            'n' => 1,
          ],
        ],
      ];
    }

    // Add the costy action space
    foreach ($spaces as $space) {
      $space['uid'] .= 'c';
      $space['cost'] = 3;
      $spaces[] = $space;
    }

    $flow = [
      'type' => NODE_SEQ,
      'childs' => [
        self::payNode([CREDIT => 1]),
        [
          'action' => \TAKE_CONTRACT,
          'args' => [
            'n' => 2,
          ],
        ],
      ],
    ];

    $spaces[] = [
      'board' => self::$id,
      'uid' => self::$id . '-t',
      'nEngineers' => 2,
      'flow' => $flow,
    ];

    $spaces[] = [
      'board' => self::$id,
      'uid' => self::$id . '-tbis',
      'nEngineers' => 3,
      'flow' => $flow,
    ];

    return $spaces;
  }
}
