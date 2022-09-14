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

  public function getUiStructure($filterCId = null)
  {
    $rows = [];
    foreach (Companies::getAll() as $cId => $company) {
      if (!is_null($filterCId) && $cId != $filterCId) {
        continue;
      }

      if ($company->isAI()) {
        $rows[] = [$cId, ['i' => '<CONSTRUCT>', 't' => clienttranslate('Construct a structure')]];
      } else {
        for ($i = 1; $i <= 4; $i++) {
          $rows[] = [$cId . '-' . $i, ['i' => '<CONSTRUCT>', 't' => clienttranslate('Construct a structure')]];
        }
      }
    }

    return $rows;
  }

  public function getAvailableSpaces()
  {
    $spaces = [];
    foreach (Companies::getAll() as $cId => $company) {
      if ($company->isAI()) {
        $spaces[] = [
          'board' => self::$id,
          'cId' => $cId,
          'uid' => self::$id . '-' . $cId,
          'cost' => 0,
          'nEngineers' => 12,
          'flow' => [
            'action' => CONSTRUCT,
          ],
        ];
      } else {
        for ($i = 1; $i <= 4; $i++) {
          $spaces[] = [
            'board' => self::$id,
            'cId' => $cId,
            'uid' => self::$id . '-' . $cId . '-' . $i,
            'cost' => $i == 4 ? 3 : 0,
            'nEngineers' => min(3, $i),
            'construct' => true,
            'flow' => [
              'action' => CONSTRUCT,
            ],
          ];
        }
      }
    }

    return $spaces;
  }

  public function getSpacesOrderForAutoma()
  {
    return [];
  }
}
