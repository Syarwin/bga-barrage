<?php
namespace BRG\Managers;
use BRG\Core\Stats;
use BRG\Helpers\UserException;

/* Class to manage all the meeples for Barrage */

class TechnologyTiles extends \BRG\Helpers\Pieces
{
  protected static $table = 'technology_tiles';
  protected static $autoremovePrefix = false;
  protected static $prefix = 'tile_';
  protected static $customFields = ['type', 'company_id'];

  protected static function cast($row)
  {
    if (in_array($row['type'], BASIC_TILES)) {
      return new \BRG\TechTiles\BasicTile($row);
    } else {
      die('TODO : Advanced tech tiles not implemented');
    }
  }

  public static function getUiData()
  {
    return self::getSelectQuery()
      ->get()
      ->toArray();
  }

  /* Creation of advanced tech tiles */
  public static function setupAdvancedTiles()
  {
    // // TODO:
    die('todo : advanced tech tiles');
  }

  public static function setupCompanies($companies)
  {
    $meeples = [];
    foreach ($companies as $cId => $company) {
      $meeples[] = ['type' => BASE, 'company_id' => $cId, 'location' => 'company'];
      $meeples[] = ['type' => ELEVATION, 'company_id' => $cId, 'location' => 'company'];
      $meeples[] = ['type' => CONDUIT, 'company_id' => $cId, 'location' => 'company'];
      $meeples[] = ['type' => POWERHOUSE, 'company_id' => $cId, 'location' => 'company'];
      $meeples[] = ['type' => JOKER, 'company_id' => $cId, 'location' => 'company'];
    }

    return self::getMany(self::create($meeples));
  }

  /**
   * Generic base query
   */
  public function getFilteredQuery($cId, $location)
  {
    $query = self::getSelectQuery();
    if ($cId != null) {
      $query = $query->where('company_id', $cId);
    }
    if ($location != null) {
      $query = $query->where('tile_location', $location);
    }
    return $query;
  }

  public function getOnWheel($cId, $slot)
  {
    return self::getFilteredQuery($cId, 'wheel')
      ->where('tile_state', $slot)
      ->get();
  }
}
