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
    // COMPANY_NETHERLANDS => 'Netherlands', // TODO: LWP
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
    self::DB()->insert([
      'id' => $cId,
      'no' => self::count() - self::getAll()->count(),
      'player_id' => $player->getId(),
      'name' => $player->getName(),
      'xo' => $xId,
      'score' => 10,
      'score_aux' => 0,
    ]);

    // Change the player color
    $player->setColor(static::$colorMapping[$cId]);
    Stats::setNation($player->getId(), $cId);
    Stats::setOfficer($player->getId(), $xId);
    return self::get($cId);
  }

  public function assignCompanyAutoma($fakePId, $cId, $xId)
  {
    $name = clienttranslate('Automa I');
    if($fakePId < -5){
      $name = clienttranslate('Automa II');
    }
    if($fakePId < -10){
      $name = clienttranslate('Automa III');
    }

    self::DB()->insert([
      'id' => $cId,
      'no' => self::count() - self::getAll()->count(),
      'player_id' => $fakePId,
      'name' => $name,
      'xo' => $xId,
      'score' => 16,
      'score_aux' => 0,
    ]);
    return self::get($cId);
  }

  public function getCorrespondingIds($pIds)
  {
    return self::DB()
      ->whereIn('player_id', $pIds)
      ->get()
      ->getIds();
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
  public function count()
  {
    // TODO : remove
    $n = Globals::getCountCompanies();
    return $n == 0 ? self::getAll()->count() : $n;
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

  public function getOpponentIds($company)
  {
    $cId = is_int($company) ? $company : $company->getId();
    $otherIds = self::getAll()->getIds();
    Utils::filter($otherIds, function ($cId2) use ($cId) {
      return $cId != $cId2;
    });
    return $otherIds;
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
}
