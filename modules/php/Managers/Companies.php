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
    $classes = [
      COMPANY_USA => 'USA',
      COMPANY_ITALY => 'Italy',
      COMPANY_FRANCE => 'France',
      COMPANY_GERMANY => 'Germany',
      COMPANY_NETHERLANDS => 'Netherlands',
    ];

    $className = '\BRG\Companies\\' . $classes[$row['id']];
    return new $className($row);
  }

  public static $colorMapping = [
    COMPANY_USA => 'be2748',
    COMPANY_GERMANY => '1b1b1b',
    COMPANY_ITALY => '13757e',
    COMPANY_FRANCE => 'ffffff',
    COMPANY_NETHERLANDS => 'ea4e1b',
  ];

  public function setupNewGame(&$players, $options)
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
        $lvl = $options[110 + $i];
        $mapping[-3 * ($i + 1) + $lvl] = $c;
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
      $lvl = $options[110 + $i];
      $id = -3 * ($i + 1) + $lvl;
      if (!\array_key_exists($id, $mapping)) {
        shuffle($companies);
        $c = array_pop($companies);
        $mapping[$id] = $c;
      }
    }

    // Create the companies
    $query = self::DB()->multipleInsert(['id', 'no', 'player_id', 'name', 'score', 'score_aux']);
    Utils::shuffle($mapping);
    $values = [];
    $order = [];
    $no = 0;
    foreach ($mapping as $pId => $company) {
      $order[] = $company;
      $values[] = [$company, $no++, $pId, $players[$pId]['player_name'] ?? clienttranslate('Automa'), 0, 0];
    }
    $query->values($values);
    Globals::setTurnOrder($order);

    // Update player colors
    foreach ($players as $pId => &$player) {
      $player['color'] = self::$colorMapping[$mapping[$pId]];
    }

    return $mapping;
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
   * Return the number of companies
   */
  protected static $nCompanies = null;
  public function count()
  {
    if (is_null(self::$nCompanies)) {
      self::$nCompanies = self::DB()->count();
    }
    return self::$nCompanies;
  }

  public function getAll()
  {
    return self::DB()->get();
  }

  /*
   * Get current turn order
   */
  public function getTurnOrder()
  {
    return Globals::getTurnOrder();
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

  /**
   * Emulate active company via a global
   */
  public function getActiveId()
  {
    return Globals::getActiveCompany();
  }

  public function getActive()
  {
    return self::get(self::getActiveId());
  }

  public function changeActive($company)
  {
    if (is_int($company)) {
      $company = self::get($company);
    }
    $companyId = $company->getId();
    Globals::setActiveCompany($companyId);
    if (!$company->isAI()) {
      Game::get()->gamestate->changeActivePlayer($company->getPId());
    }
  }

  /////////////////
  // TODO
  /////////////////

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
}
