<?php
namespace BRG\Managers;

/* Class to manage all the automa cards for Barrage */

class AutomaCards extends \BRG\Helpers\Pieces
{
  protected static $table = 'cards';
  protected static $prefix = 'card_';
  protected static $autoIncrement = false;
  protected static $autoremovePrefix = false;

  protected static function cast($row)
  {
    return self::getInstance($row['card_id'], $row);
  }

  protected function getInstance($id, $row = null)
  {
    $className = '\BRG\AutomaCards\Card' . $id;
    return new $className($row);
  }

  public static function getUiData()
  {
    return []; // TODO
  }

  public static function setupNewGame($options)
  {
    if ($options[\BRG\OPTION_AUTOMA] == 0) {
      return;
    }

    $cards = [];
    for ($i = 1; $i < 7; $i++) {
      $cards[] = [
        'id' => $i,
        'location' => 'deck',
      ];
    }

    self::create($cards);
  }
}
