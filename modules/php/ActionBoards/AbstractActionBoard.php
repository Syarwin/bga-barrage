<?php
namespace BRG\ActionBoards;

use BRG\Managers\Meeples;
use BRG\Managers\Companies;
use BRG\Core\Notifications;
use BRG\Core\Engine;
use BRG\Core\Globals;
use BRG\Helpers\Utils;

/*
 * Action space board
 */

abstract class AbstractActionBoard
{
  protected static $id;
  public static function getId()
  {
    return static::$id;
  }
  public static function getName()
  {
    return '';
  }

  // Constraints
  protected static $players = null; // Players requirements => null if none, array otherwise
  protected static $isNotBeginner = false; // Will NOT be there on the beginner variant
  protected static $isLWP = false; // Will only be there if the LWP expansions is on

  public function isSupported()
  {
    return (self::$players == null || in_array(Companies::count(), self::$players)) &&
      (!self::$isNotBeginner || !Globals::isBeginner()) &&
      (!self::$isLWP || !Globals::isLWP());
  }

  /**
   * Get the list of action spaces corresponding to that board
   *  => depends only on player count
   */
  abstract public function getAvailableSpaces();

  /**
   * getUiData : remove useless data for frontend, as the flow
   *  and organize this depending on board structure
   */
//  abstract protected function getUiStructure();
  protected function getUiStructure(){return [];}
  public function getUiData()
  {
    $spaces = [];
    foreach (static::getAvailableSpaces() as $space) {
      unset($space['flow']);
      $spaces[$space['uid']] = $space;
    }

    $structure = static::getUiStructure();
    foreach ($structure as &$row) {
      foreach ($row as $i => $elem) {
        $key = static::$id . '-' . $elem;
        if (\array_key_exists($key, $spaces)) {
          $row[$i] = $spaces[$key];
        }
      }
    }

    return $structure;
  }

  /**
   * getPlayableSpaces : return the list of playable spaces for a given company
   */
  public function getPlayableSpaces($company)
  {
    $spaces = self::getAvailableSpaces();

    // Is there an engineer here ?
    Utils::filter($spaces, function ($space) {
      return Meeples::getOnSpace($space['uid'])->empty();
    });

    /*
    // Check that the action is doable
    $flow = $this->getFlow($player);
    $flowTree = Engine::buildTree($flow);
    return $flowTree->isDoable($player);
*/

    return $spaces;
  }

  /**
   * Tag all the subtree flow with the information about this card so we can access it in the ctx later
   *
  protected function tagTree($t, $player)
  {
    $t['cardId'] = $this->id;
    $t['pId'] = $player->getId();
    if (isset($t['childs'])) {
      $t['childs'] = array_map(function ($child) use ($player) {
        return $this->tagTree($child, $player);
      }, $t['childs']);
    }
    return $t;
  }
  */

  /*
  public function getFlow($player)
  {
    return $this->tagTree($this->flow, $player); // Add card context for listeners
  }
*/
}
