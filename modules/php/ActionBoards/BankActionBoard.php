<?php
namespace BRG\ActionBoards;

use BRG\Managers\Meeples;
use BRG\Managers\Companies;
use BRG\Core\Notifications;
use BRG\Core\Engine;
use BRG\Core\Globals;

/*
 * Bank Action space board
 */

class BankActionBoard extends AbstractActionBoard
{
  protected static $id = BOARD_BANK;
  public static function getName()
  {
    return clienttranslate('Bank');
  }

  protected function getUiStructure($cId = null)
  {
    $rows = [];

    $rows[] = ['b'];

    return $rows;
  }

  public function getAvailableSpaces()
  {
    $spaces = [];

    $spaces[] = [
      'board' => self::$id,
      'uid' => self::$id . '-b',
      'nEngineers' => 0,
      'exclusive' => false,
      'flow' => static::gainNode([\CREDIT => 1]),
      'tooltip' => clienttranslate(
        'Take a number of Credits equal to the number of Engineers you placed in this action space'
      ),
    ];

    return $spaces;
  }

  public function getSpacesOrderForAutoma()
  {
    return ['b'];
  }
}
