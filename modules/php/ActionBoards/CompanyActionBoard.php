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
      for ($i = 1; $i <= 4; $i++) {
        $rows[] = [$cId . '-' . $i, ['i' => '<CONSTRUCT>', 't' => clienttranslate('Construct a structure')]];
      }
      if ($company->isXO(\XO_MAHIRI)) {
        $rows[] = [$cId . '-6', ['i' => '<MAHIRI>', 't' => clienttranslate('Copy an executive officer power')]];
        $rows[] = [$cId . '-7', ['i' => '<MAHIRI>', 't' => clienttranslate('Copy an executive officer power')]];
      }
    }

    return $rows;
  }

  public function getUiData($cId = null)
  {
    $spaces = [];
    foreach (static::getAvailableSpaces() as $space) {
      if (!is_null($cId) && $space['cId'] != $cId) {
        continue;
      }
      unset($space['flow']);
      $spaces[$space['uid']] = $space;
    }

    $structure = static::getUiStructure($cId);
    foreach ($structure as &$row) {
      if (is_array($row)) {
        foreach ($row as $i => $elem) {
          if (is_array($elem)) {
            continue;
          }

          $key = static::$id . '-' . $elem;
          if (\array_key_exists($key, $spaces)) {
            $row[$i] = $spaces[$key];
          }
        }
      }
    }

    return $structure;
  }

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
          'flow' => [
            'action' => CONSTRUCT,
          ],
        ];
      }
      if ($company->isXO(\XO_MAHIRI)) {
        $spaces[] = [
          'board' => self::$id,
          'cId' => $cId,
          'uid' => self::$id . '-' . $cId . '-6',
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
          'uid' => self::$id . '-' . $cId . '-7',
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
