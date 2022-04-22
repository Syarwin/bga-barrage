<?php
namespace BRG\ActionBoards;

use BRG\Managers\Meeples;
use BRG\Managers\Companies;
use BRG\Core\Notifications;
use BRG\Core\Engine;
use BRG\Core\Globals;

/*
 * Company action space board
 */

class CompanyActionBoard extends AbstractActionBoard
{
  protected static $id = BOARD_COMPANY;

  public function getAvailableSpaces()
  {
    $spaces = [];
    foreach (Companies::getAll() as $cId => $company) {
      for ($i = 1; $i <= 4; $i++) {
        $spaces[] = [
          'board' => self::$id,
          'cId' => $cId,
          'uid' => self::$id . '-' . $cId . '-' . $i,
          'cost' => $i == 4 ? 3 : 0,
          'nEngineers' => min(3, $i),
          'tooltip' => clienttranslate('Construct a structure'),
          'flow' => [
            'action' => CONSTRUCT,
          ],
        ];
      }
    }

    return $spaces;
  }
}
