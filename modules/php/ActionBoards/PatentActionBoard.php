<?php
namespace BRG\ActionBoards;

use BRG\Managers\Meeples;
use BRG\Managers\Companies;
use BRG\Core\Notifications;
use BRG\Core\Engine;
use BRG\Core\Globals;

/*
 * Patent Management action space board
 */

class PatentActionBoard extends AbstractActionBoard
{
  protected static $id = BOARD_PATENT;
  public static function getName()
  {
    return clienttranslate('Patent office');
  }

  public function getUiStructure()
  {
    $rows = [];

    $rows[] = ['p1', ['i' => '<PATENT:1>', 't' => clienttranslate('Acquire a level 1 tile')], 'p1c'];
    $rows[] = ['p2', ['i' => '<PATENT:2>', 't' => clienttranslate('Acquire a level 2 tile')], 'p2c'];
    $rows[] = ['p3', ['i' => '<PATENT:3>', 't' => clienttranslate('Acquire a level 3 tile')], 'p3c'];

    return $rows;
  }

  public function getAvailableSpaces()
  {
    $spaces = [];

    $spaces[] = [
      'board' => self::$id,
      'uid' => self::$id . '-p1',
      'nEngineers' => 2,
      'flow' => [
        'type' => NODE_SEQ,
        'childs' => [
          static::payNode([CREDIT => 5]),
          [
            'action' => PATENT,
            'args' => [
              'position' => 1,
            ],
          ],
        ],
      ],
    ];

    $spaces[] = [
      'board' => self::$id,
      'uid' => self::$id . '-p2',
      'nEngineers' => 2,
      'flow' => [
        'type' => NODE_SEQ,
        'childs' => [
          static::payNode([CREDIT => 5]),
          [
            'action' => PATENT,
            'args' => [
              'position' => 2,
            ],
          ],
        ],
      ],
    ];

    $spaces[] = [
      'board' => self::$id,
      'uid' => self::$id . '-p3',
      'nEngineers' => 2,
      'flow' => [
        'type' => NODE_SEQ,
        'childs' => [
          static::payNode([CREDIT => 5]),
          [
            'action' => PATENT,
            'args' => [
              'position' => 3,
            ],
          ],
        ],
      ],
    ];
    return $spaces;
  }
}
