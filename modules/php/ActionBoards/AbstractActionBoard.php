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
  // Get the order for this list of action spaces
  abstract public function getSpacesOrderForAutoma();

  /**
   * getUiData : remove useless data for frontend, as the flow
   *  and organize this depending on board structure
   */
  protected function getUiStructure($cId = null)
  {
    return [];
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

  /**
   * getPlayableSpaces : return the list of playable spaces for a given company
   */
  public function getPlayableSpaces($company)
  {
    $spaces = static::getAvailableSpaces();

    // Filter private spaces
    Utils::filter($spaces, function ($space) use ($company) {
      return !isset($space['cId']) || $space['cId'] == $company->getId();
    });

    return $spaces;
  }

  /**
   * getOrderedPlayableSpaces: for Automa, return the list of playable spaces in the top-bottom/left-right order of the board
   */
  public function getOrderedPlayableSpaces($company)
  {
    $spaces = static::getPlayableSpaces($company);
    $order = array_map(function ($space) {
      return self::$id . '-' . $space;
    }, static::getSpacesOrderForAutoma());
    usort($spaces, function ($a, $b) use ($order) {
      \array_search($a, $order) < \array_search($b, $order) ? -1 : 1;
    });
    return $spaces;
  }

  public function gainNode($gain, $pId = null)
  {
    return [
      'action' => GAIN,
      'args' => $gain,
      'source' => static::getName(),
    ];
  }

  public function payNode($cost, $sourceName = null, $nb = 1)
  {
    return [
      'action' => PAY,
      'args' => [
        'nb' => $nb,
        'costs' => Utils::formatCost($cost),
        'source' => $sourceName ?? static::getName(),
      ],
    ];
  }

  public function payGainNode($cost, $gain, $sourceName = null, $optional = false)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => $optional,
      'childs' => [static::payNode($cost, $sourceName), static::gainNode($gain)],
    ];
  }
}
