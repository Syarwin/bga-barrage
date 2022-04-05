<?php
namespace BRG\Managers;
use BRG\Core\Game;
use BRG\Core\Globals;
use BRG\Core\Stats;
use BRG\Helpers\Utils;

/*
 * Companies manager : allows to easily access players, including automas
 */
class Companies extends \BRG\Helpers\DB_Manager
{
  protected static $table = 'companies';
  protected static $primary = 'id';
  protected static function cast($row)
  {
    return new \BRG\Models\Company($row);
  }

  public function setupNewGame($players, $options)
  {
    // Allocate companies
    $companies = [\COMPANY_USA, \COMPANY_ITALY, \COMPANY_FRANCE, \COMPANY_GERMANY, \COMPANY_NETHERLANDS];

    // Compute player order around the table
    $orderTable = [];
    foreach ($players as $pId => $player) {
      $orderTable[$player['player_table_order']] = $pId;
    }
    ksort($orderTable);

    // We are building a mapping pId => company
    $mapping = [];

    // Assign human players with preference first
    $i = 0;
    foreach ($orderTable as $order => $pId) {
      $c = $options[101 + $i] ?? null;
      if ($c != null && in_array($c, $companies)) {
        $mapping[$pId] = $c;
        array_splice($companies, array_search($c, $companies), 1);
      }
      $i++;
    }

    // Assign IA players with preference
    for ($i = 0; $i < 4; $i++) {
      $c = $options[106 + $i] ?? null;
      if ($c != null && in_array($c, $companies)) {
        $mapping[-$i - 1] = $c;
        array_splice($companies, array_search($c, $companies), 1);
      }
    }

    // Assign remaining players
    foreach ($orderTable as $order => $pId) {
      if (!\array_key_exists($pId, $mapping)) {
        shuffle($companies);
        $c = array_pop($companies);
        $mapping[$pId] = $c;
      }
    }

    for ($i = 0; $i < $options[\BRG\OPTION_AUTOMA]; $i++) {
      if (!\array_key_exists(-$i - 1, $mapping)) {
        shuffle($companies);
        $c = array_pop($companies);
        $mapping[-$i - 1] = $c;
      }
    }


    // Create the companies
    $query = self::DB()->multipleInsert(['id', 'no', 'player_id', 'name', 'score', 'score_aux']);
    Utils::shuffle($mapping);
    $values = [];
    $no = 0;
    foreach ($mapping as $pId => $company) {
      $values[] = [$company, $no++, $pId, $players[$pId]['player_name'] ?? 'IA', 0, 0];
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

  /*
   * Get current turn order according to first player variable
   */
  public function getTurnOrder($firstPlayer = null)
  {
    $firstPlayer = $firstPlayer ?? Globals::getFirstPlayer();
    $order = [];
    $p = $firstPlayer;
    do {
      $order[] = $p;
      $p = self::getNextId($p);
    } while ($p != $firstPlayer);
    return $order;
  }
}
