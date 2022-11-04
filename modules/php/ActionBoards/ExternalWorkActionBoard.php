<?php
namespace BRG\ActionBoards;

use BRG\Managers\Meeples;
use BRG\Managers\Companies;
use BRG\Core\Notifications;
use BRG\Core\Engine;
use BRG\Core\Globals;

/*
 * External Work Management action space board
 */

class ExternalWorkActionBoard extends AbstractActionBoard
{
  protected static $id = BOARD_EXTERNAL_WORK;
  protected static $isNotBeginner = true;
  protected static $isLWP = true;
  public static function getName()
  {
    return clienttranslate('External Works');
  }

  public function getUiStructure($cId = null)
  {
    $rows = [];

    $rows[] = ['w1', ['i' => '', 't' => clienttranslate('Fulfill this External Work')]];
    $rows[] = ['w2', ['i' => '', 't' => clienttranslate('Fulfill this External Work')]];
    $rows[] = ['w3', ['i' => '', 't' => clienttranslate('Fulfill this External Work')]];
    $rows[] = 'work_1';
    $rows[] = 'work_2';
    $rows[] = 'work_3';

    return $rows;
  }

  public function getAvailableSpaces()
  {
    $spaces = [];

    $spaces[] = [
      'board' => self::$id,
      'uid' => self::$id . '-w1',
      'nEngineers' => 2,
      'flow' => [
        'action' => EXTERNAL_WORK,
        'args' => [
          'position' => 1,
        ],
      ],
    ];

    $spaces[] = [
      'board' => self::$id,
      'uid' => self::$id . '-w2',
      'nEngineers' => 2,
      'flow' => [
        'action' => EXTERNAL_WORK,
        'args' => [
          'position' => 2,
        ],
      ],
    ];

    $spaces[] = [
      'board' => self::$id,
      'uid' => self::$id . '-w3',
      'nEngineers' => 2,
      'flow' => [
        'action' => EXTERNAL_WORK,
        'args' => [
          'position' => 3,
        ],
      ],
    ];

    return $spaces;
  }

  public function getSpacesOrderForAutoma()
  {
    return [];
  }
}
