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

  public static function getUiStructure($filterCId = null)
  {
    $rows = [];
    foreach (Companies::getAll() as $cId => $company) {
      if (!is_null($filterCId) && $cId != $filterCId) {
        continue;
      }

      $company->getOfficer()->addActionSpacesUi($rows);
    }

    return $rows;
  }

  public static function getAvailableSpaces()
  {
    $spaces = [];
    foreach (Companies::getAll() as $cId => $company) {
      $company->getOfficer()->addActionSpaces($spaces);
    }

    return $spaces;
  }

  public static function getSpacesOrderForAutoma()
  {
    return [];
  }
}
