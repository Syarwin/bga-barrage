<?php
namespace BRG\Managers;
use BRG\Core\Globals;
use BRG\Core\Notifications;

/* Class to manage all the automa cards for Barrage */

class AutomaCards extends \BRG\Helpers\Pieces
{
  protected static $table = 'cards';
  protected static $prefix = 'card_';
  protected static $autoIncrement = false;
  protected static $autoremovePrefix = false;
  protected static $autoreshuffleCustom = ['deck' => 'discard'];

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
    return Globals::isAI()
      ? [
        'front' => self::getTopOf('deck'),
        'back' => self::getTopOf('flipped'),
      ]
      : null;
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
    self::shuffle('deck');
  }

  public function flip()
  {
    self::moveAllInLocation('flipped', 'discard');
    $cardBack = self::pickOneForLocation('deck', 'flipped')->getId();
    if (self::countInLocation('deck') == 0) {
      self::reformDeckFromDiscard('deck');
    }
    $cardFront = self::getTopOf('deck')->getId();
    Notifications::flipAutomaCard($cardBack, $cardFront);
  }
}
