<?php
namespace BRG\ActionBoards;

use BRG\Managers\Meeples;
use BRG\Managers\Companies;
use BRG\Managers\Buildings;
use BRG\Core\Notifications;
use BRG\Core\Engine;
use BRG\Core\Globals;

/*
 * Buildings Management action space board
 */

class BuildingsActionBoard extends AbstractActionBoard
{
  protected static $id = BOARD_BUILDINGS;
  protected static $isNotBeginner = true;
  protected static $isLWP = true;
  public static function getName()
  {
    return clienttranslate('External Works');
  }

  public function getUiStructure($cId = null)
  {
    $rows = [];
    foreach (Buildings::getAll() as $bId => $building) {
      $row = [$bId, $building->getCentralIcon()];
      foreach ($building->getEngineerSpaces() as $i => $engineerCost) {
        $row[] = $bId . '-' . $i;
      }
      $rows[] = $row;
    }

    return $rows;
  }

  public function getAvailableSpaces()
  {
    $spaces = [];

    foreach (Buildings::getAll() as $bId => $building) {
      foreach ($building->getEngineerSpaces() as $i => $engineerCost) {
        $costly = mb_strpos($engineerCost, 'c') !== false;
        $nEngineers = $costly ? ((int) substr($engineerCost, 0, -1)) : $engineerCost;

        $space = [
          'board' => self::$id,
          'uid' => self::$id . '-' . $bId . '-' . $i,
          'nEngineers' => $nEngineers,
          'flow' => [
            'action' => GAIN,
            'args' => [CREDIT => 1],
          ],
        ];
        if ($costly) {
          $space['cost'] = 3;
        }

        $spaces[] = $space;
      }
    }

    return $spaces;
  }

  public function getSpacesOrderForAutoma()
  {
    return [];
  }
}