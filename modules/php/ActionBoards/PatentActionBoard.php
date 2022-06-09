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
  protected static $isNotBeginner = true;
  public static function getName()
  {
    return clienttranslate('Patent office');
  }

  public function getUiStructure()
  {
    $rows = [];

    $rows[] = ['p1', ['i' => '<COST:5><ARROW>', 't' => clienttranslate('Pay 5 Credits to acquire a level 1 tile')]];
    $rows[] = 'patent_1';
    $rows[] = ['p2', ['i' => '<COST:5><ARROW>', 't' => clienttranslate('Pay 5 Credits to acquire a level 2 tile')]];
    $rows[] = 'patent_2';
    $rows[] = ['p3', ['i' => '<COST:5><ARROW>', 't' => clienttranslate('Pay 5 Credits to acquire a level 3 tile')]];
    $rows[] = 'patent_3';

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
