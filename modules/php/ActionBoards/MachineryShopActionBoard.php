<?php
namespace BRG\ActionBoards;

use BRG\Managers\Meeples;
use BRG\Managers\Companies;
use BRG\Core\Notifications;
use BRG\Core\Engine;
use BRG\Core\Globals;

/*
 * Machinery Shop action space board
 */

class MachineryShopActionBoard extends AbstractActionBoard
{
  protected static $id = \BOARD_MACHINERY_SHOP;
  public static function getName()
  {
    return clienttranslate('Machinery Shop');
  }

  public function getAvailableSpaces()
  {
    $spaces = [];

    return $spaces;
  }
}
