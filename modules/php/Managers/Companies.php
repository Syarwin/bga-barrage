<?php
namespace BRG\Managers;
use BRG\Core\Game;
use BRG\Core\Globals;
use BRG\Core\Stats;
use BRG\Core\Notifications;
use BRG\Helpers\Utils;

/*
 * Companies manager : allows to easily access players, including automas
 */
class Companies extends \BRG\Helpers\DB_Manager
{
  protected static $classes = [
    COMPANY_USA => 'USA',
    COMPANY_ITALY => 'Italy',
    COMPANY_FRANCE => 'France',
    COMPANY_GERMANY => 'Germany',
    COMPANY_NETHERLANDS => 'Netherlands',
  ];

  protected static $table = 'companies';
  protected static $primary = 'id';
  protected static function cast($row)
  {
    return self::getInstance($row['id'], $row);
  }

  public static function getInstance($cId, $row = null)
  {
    $className = '\BRG\Companies\\' . static::$classes[$cId];
    return new $className($row);
  }

  public static $colorMapping = [
    COMPANY_USA => 'be2748',
    COMPANY_GERMANY => '1b1b1b',
    COMPANY_ITALY => '13757e',
    COMPANY_FRANCE => 'ffffff',
    COMPANY_NETHERLANDS => 'ea4e1b',
  ];

  public function randomStartingPick($nPlayers)
  {
    $companyIds = array_keys(static::$classes);
    return Utils::rand($companyIds, $nPlayers);
  }

  public function assignCompany($player, $cId, $xId)
  {
    $nAutomas = 0; // TODO

    self::DB()->insert([
      'id' => $cId,
      'no' => $nAutomas + self::count(true) + 1,
      'player_id' => $player->getId(),
      'name' => $player->getName(),
      'xo' => $xId,
      'score' => 0,
      'score_aux' => 0,
    ]);

    // Change the player color
    $player->setColor(static::$colorMapping[$cId]);
    return self::get($cId);
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
  public function count($forceRefresh = false)
  {
    if (is_null(self::$nCompanies) || $forceRefresh) {
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

  public function resetEnergies()
  {
    self::DB()
      ->update(['energy' => 0])
      ->run();
  }

  public function returnHome()
  {
    $engineers = [];
    foreach (self::getAll() as $company) {
      $engineers = array_merge($engineers, $company->returnHomeEngineers());
    }
    Notifications::returnHomeEngineers(Meeples::getMany($engineers)->toArray());
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
}
