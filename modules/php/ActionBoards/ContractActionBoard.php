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

  public function getAvailableSpaces()
  {
    $spaces = [];

    return $spaces;
  }
}
