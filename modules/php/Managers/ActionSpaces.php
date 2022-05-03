<?php
namespace BRG\Managers;

use BRG\Core\Globals;
use BRG\Core\Meeples;

/* Class to manage all the action spaces */

class ActionSpaces
{
  protected static $boards = [
    'CompanyActionBoard',
    'TurbineStationActionBoard',
    'WaterActionBoard',
    'BankActionBoard',
    'WorkshopActionBoard',
    'MachineryShopActionBoard',
    // 'ContractActionBoard',
  ];

  public function getBoard($id)
  {
    foreach (self::$boards as $name) {
      $className = '\BRG\ActionBoards\\' . $name;
      if ($className::getId() == $id) {
        return $className;
      }
    }

    return null;
  }

  protected function getBoards()
  {
    $boards = [];
    foreach (self::$boards as $name) {
      $className = '\BRG\ActionBoards\\' . $name;
      if ($className::isSupported()) {
        $boards[] = $className;
      }
    }

    return $boards;
  }

  public function getUiData()
  {
    $ui = [];
    foreach (self::getBoards() as $board) {
      if ($board::isSupported()) {
        $ui[] = [
          'id' => $board::getId(),
          'name' => $board::getName(),
          'structure' => $board::getUiData(),
        ];
      }
    }
    return $ui;
  }

  public function getPlayableSpaces($company)
  {
    $spaces = [];
    foreach (self::getBoards() as $board) {
      if ($board::isSupported()) {
        $spaces = array_merge($spaces, $board::getPlayableSpaces($company));
      }
    }
    return $spaces;
  }
}
