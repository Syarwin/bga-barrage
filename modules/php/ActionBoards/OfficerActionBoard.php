<?php
namespace BRG\ActionBoards;

use BRG\Managers\Meeples;
use BRG\Managers\Companies;
use BRG\Core\Notifications;
use BRG\Core\Engine;
use BRG\Core\Globals;

/*
 * XO action space board
 */

class OfficerActionBoard extends AbstractActionBoard
{
  protected static $id = BOARD_OFFICER;

  public function getUiStructure($filterCId = null)
  {
    $rows = [];
    foreach (Companies::getAll() as $cId => $company) {
      if (!is_null($filterCId) && $cId != $filterCId) {
        continue;
      }

      if ($company->isXO(\XO_MAHIRI)) {
        $rows[] = [
          'mahiri-1',
          [
            'i' => '<MAHIRI>',
            't' => clienttranslate(
              'You have a personal special ability that you can activate placing 1 Engineer on the action space of this tile. If you use it a second time during the same round, you must also pay 3 Credits. When you activate it, you can copy another Executive Officer\'s special ability.'
            ),
          ],
          'mahiri-2',
        ];
      }
    }

    return $rows;
  }

  public function getAvailableSpaces()
  {
    $spaces = [];
    foreach (Companies::getAll() as $cId => $company) {
      if ($company->isXO(\XO_MAHIRI)) {
        $spaces[] = [
          'board' => self::$id,
          'cId' => $cId,
          'uid' => self::$id . '-mahiri-1',
          'cost' => 0,
          'nEngineers' => 1,
          'flow' => [
            'action' => \SPECIAL_EFFECT,
            'args' => ['xoId' => \XO_MAHIRI, 'method' => 'copyPower'],
          ],
        ];
        $spaces[] = [
          'board' => self::$id,
          'cId' => $cId,
          'uid' => self::$id . '-mahiri-2',
          'cost' => 3,
          'nEngineers' => 1,
          'flow' => [
            'action' => \SPECIAL_EFFECT,
            'args' => ['xoId' => \XO_MAHIRI, 'method' => 'copyPower'],
          ],
        ];
      }
    }

    return $spaces;
  }
}
