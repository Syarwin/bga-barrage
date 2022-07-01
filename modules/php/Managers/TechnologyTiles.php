<?php
namespace BRG\Managers;
use BRG\Core\Stats;
use BRG\Helpers\UserException;
use BRG\Core\Notifications;
use BRG\Core\Globals;
use BRG\Helpers\Collection;

/* Class to manage all the meeples for Barrage */

class TechnologyTiles extends \BRG\Helpers\Pieces
{
  protected static $table = 'technology_tiles';
  protected static $autoremovePrefix = false;
  protected static $prefix = 'tile_';
  protected static $customFields = ['type', 'company_id'];

  static $classes = [
    L1_BASE => 'L1Base',
    L1_ELEVATION => 'L1Elevation',
    L1_CONDUIT => 'L1Conduit',
    L1_POWERHOUSE => 'L1Powerhouse',
    L1_JOKER => 'L1Joker',
    L2_BASE => 'L2Base',
    L2_ELEVATION => 'L2Elevation',
    L2_CONDUIT => 'L2Conduit',
    L2_POWERHOUSE => 'L2Powerhouse',
    L2_JOKER => 'L2Joker',
    L3_BASE => 'L3Base',
    L3_ELEVATION => 'L3Elevation',
    L3_CONDUIT => 'L3Conduit',
    L3_POWERHOUSE => 'L3Powerhouse',
    L3_JOKER => 'L3Joker',
  ];

  protected static function cast($row)
  {
    if (in_array($row['type'], BASIC_TILES)) {
      return new \BRG\TechTiles\BasicTile($row);
    } elseif ($row['type'] == ANTON_TILE) {
      return new \BRG\TechTiles\AntonTile($row);
    } else {
      if (!\array_key_exists($row['type'], self::$classes)) {
        throw new \BgaVisibleSystemException(
          'Trying to get an advanced tile not defined in TechnologyTiles.php : ' . $row['type']
        );
      }
      $name = '\BRG\TechTiles\\' . self::$classes[$row['type']];
      return new $name($row);
    }
  }

  public static function getUiData()
  {
    return self::getSelectQuery()
      ->whereNotIn('tile_location', ['deckL1', 'deckL2', 'deckL3'])
      ->get()
      ->toArray();
  }

  /**
   * Generic base query
   */
  public function getFilteredQuery($cId, $location = null)
  {
    $query = self::getSelectQuery();
    if ($cId != null) {
      $query = $query->where('company_id', $cId);
    }
    if ($location != null) {
      $query = $query->where('tile_location', strpos($location, '%') === false ? '=' : 'LIKE', $location);
    }
    return $query;
  }

  public function getOnWheel($cId, $slot)
  {
    return self::getFilteredQuery($cId, 'wheel')
      ->where('tile_state', $slot)
      ->get();
  }

  ///////////////////////////////////
  //  ____       _
  // / ___|  ___| |_ _   _ _ __
  // \___ \ / _ \ __| | | | '_ \
  //  ___) |  __/ |_| |_| | |_) |
  // |____/ \___|\__|\__,_| .__/
  //                      |_|
  ///////////////////////////////////

  public static function setupCompany($company)
  {
    $cId = $company->getId();
    $meeples = [];
    $meeples[] = ['type' => BASE, 'company_id' => $cId, 'location' => 'company'];
    $meeples[] = ['type' => ELEVATION, 'company_id' => $cId, 'location' => 'company'];
    $meeples[] = ['type' => CONDUIT, 'company_id' => $cId, 'location' => 'company'];
    $meeples[] = ['type' => POWERHOUSE, 'company_id' => $cId, 'location' => 'company'];
    if (Globals::isBeginner()) {
      $meeples[] = ['type' => JOKER, 'company_id' => $cId, 'location' => 'company'];
    }

    if ($company->isXO(\XO_ANTON)) {
      $meeples[] = ['type' => \ANTON_TILE, 'company_id' => $cId, 'location' => 'company'];
    }
    return self::getMany(self::create($meeples));
  }

  public static function setupCompanies($companies)
  {
    $meeples = new Collection();
    foreach ($companies as $cId => $company) {
      $meeples = $meeples->merge(self::setupCompany($company));
    }

    return $meeples;
  }

  /* Creation of advanced tech tiles */
  public static function setupAdvancedTiles()
  {
    $meeples = [];
    foreach (L1_TILES as $type) {
      $meeples[] = ['type' => $type, 'location' => 'deckL1'];
    }
    foreach (L2_TILES as $type) {
      $meeples[] = ['type' => $type, 'location' => 'deckL2'];
    }
    foreach (L3_TILES as $type) {
      $meeples[] = ['type' => $type, 'location' => 'deckL3'];
    }

    self::create($meeples);

    for ($i = 1; $i <= 3; $i++) {
      // shuffle each deck
      self::shuffle('deckL' . $i);

      // pick the advanced tiles
      self::pickForLocation(1, 'deckL1', 'patent_' . $i);
    }
    return;
  }

  //////////////////////////////////////////////////////////////
  //  _   _                 ____                       _
  // | \ | | _____      __ |  _ \ ___  _   _ _ __   __| |
  // |  \| |/ _ \ \ /\ / / | |_) / _ \| | | | '_ \ / _` |
  // | |\  |  __/\ V  V /  |  _ < (_) | |_| | | | | (_| |
  // |_| \_|\___| \_/\_/   |_| \_\___/ \__,_|_| |_|\__,_|
  //
  //////////////////////////////////////////////////////////////
  public function newRound()
  {
    // discard all tiles
    $tiles = self::getFilteredQuery(null, 'patent_%')->get();
    $deleted = [];
    foreach ($tiles as $tId => $tile) {
      $deleted[] = $tId;
      self::DB()->delete($tId);
    }

    if (count($deleted) > 0) {
      Notifications::discardTiles($deleted);
    }

    // draw new ones
    $created = new Collection();
    $deck = 1;
    for ($i = 1; $i <= 3; $i++) {
      $created = $created->merge(self::pickForLocation(1, 'deckL' . $deck, 'patent_' . $i, null, false));
      if (count($created) < $i) {
        $deck++;
        $created = $created->merge(self::pickForLocation(1, 'deckL' . $deck, 'patent_' . $i, null, false) ?? []);
        if (count($created) < $i) {
          $deck++;
          $created = $created->merge(self::pickForLocation(1, 'deckL' . $deck, 'patent_' . $i, null, false) ?? []);
        }
      }
    }
    Notifications::refillTechTiles($created);
  }
}
