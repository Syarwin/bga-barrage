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

  public function getAvailableSpaces()
  {
    $spaces = [];

    return $spaces;
  }
}
