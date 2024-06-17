<?php

namespace BRG\Managers;

use BRG\Core\Game;
use BRG\Core\Globals;
use BRG\Core\Stats;
use BRG\Helpers\Utils;

/*
 * Players manager : allows to easily access players ...
 *  a player is an instance of Player class
 */

class Players extends \BRG\Helpers\DB_Manager
{
  protected static $table = 'player';
  protected static $primary = 'player_id';
  protected static function cast($row)
  {
    return new \BRG\Models\Player($row);
  }

  public static function setupNewGame($players, $options)
  {
    // Create players
    $query = self::DB()->multipleInsert([
      'player_id',
      'player_color',
      'player_canal',
      'player_name',
      'player_avatar',
      'player_score',
    ]);

    $values = [];
    $colors = ['ff0000', '008000', '0000ff', 'ffa500', '773300'];
    foreach ($players as $pId => $player) {
      $color = array_shift($colors);
      $values[] = [$pId, $color, $player['player_canal'], $player['player_name'], $player['player_avatar'], 10];
    }
    $query->values($values);
  }

  public static function getActiveId()
  {
    return Game::get()->getActivePlayerId();
  }

  public static function getCurrentId()
  {
    return Game::get()->getCurrentPId();
  }

  public static function getAll()
  {
    return self::DB()->get(false);
  }

  /*
   * get : returns the Player object for the given player ID
   */
  public static function get($pId = null)
  {
    $pId = $pId ?: self::getActiveId();
    return self::DB()
      ->where($pId)
      ->getSingle();
  }

  public static function getActive()
  {
    return self::get();
  }

  public static function getCurrent()
  {
    return self::get(self::getCurrentId());
  }

  public static function getNextId($player)
  {
    $pId = is_int($player) ? $player : $player->getId();
    $table = Game::get()->getNextPlayerTable();
    return $table[$pId];
  }

  /*
   * Return the number of players
   */
  public static function count()
  {
    return self::DB()->count();
  }

  public static function returnHome()
  {
    foreach (self::getAll() as $player) {
      $player->returnHomeFarmers();
    }
  }

  /*
   * getUiData : get all ui data of all players
   */
  public static function getUiData($pId)
  {
    return self::getAll()->map(function ($player) use ($pId) {
      return $player->jsonSerialize($pId);
    });
  }
}
