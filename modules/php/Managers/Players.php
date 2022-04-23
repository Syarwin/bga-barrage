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

  public function setupNewGame($players, $options)
  {
    // Create players
    $query = self::DB()->multipleInsert([
      'player_id',
      'player_color',
      'player_canal',
      'player_name',
      'player_avatar',
    ]);

    $values = [];
    foreach ($players as $pId => $player) {
      $values[] = [$pId, $player['color'], $player['player_canal'], $player['player_name'], $player['player_avatar']];
    }
    $query->values($values);
  }

  public function getActiveId()
  {
    return Game::get()->getActivePlayerId();
  }

  public function getCurrentId()
  {
    return Game::get()->getCurrentPId();
  }

  public function getAll()
  {
    return self::DB()->get(false);
  }

  /*
   * get : returns the Player object for the given player ID
   */
  public function get($pId = null)
  {
    $pId = $pId ?: self::getActiveId();
    return self::DB()
      ->where($pId)
      ->getSingle();
  }

  public function getActive()
  {
    return self::get();
  }

  public function getCurrent()
  {
    return self::get(self::getCurrentId());
  }

  public function getNextId($player)
  {
    $pId = is_int($player) ? $player : $player->getId();
    $table = Game::get()->getNextPlayerTable();
    return $table[$pId];
  }

  /*
   * Return the number of players
   */
  public function count()
  {
    return self::DB()->count();
  }

  public function countUnallocatedFarmers()
  {
    // Get zombie players ids
    $zombies = self::getAll()
      ->filter(function ($player) {
        return $player->isZombie();
      })
      ->getIds();

    // Filter out farmers of zombies
    return Farmers::getAllAvailable()
      ->filter(function ($meeple) use ($zombies) {
        return !in_array($meeple['pId'], $zombies);
      })
      ->count();
  }

  public function returnHome()
  {
    foreach (self::getAll() as $player) {
      $player->returnHomeFarmers();
    }
  }

  /*
   * getUiData : get all ui data of all players
   */
  public function getUiData($pId)
  {
    return self::getAll()->map(function ($player) use ($pId) {
      return $player->jsonSerialize($pId);
    });
  }
}
