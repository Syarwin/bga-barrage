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
  protected static $customFields = ['structure', 'company_id'];

  protected static function cast($row)
  {
    if (in_array($row['structure'], BASIC_TILES)) {
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
      $meeples[] = ['structure' => BASE, 'company_id' => $cId, 'location' => 'company'];
      $meeples[] = ['structure' => ELEVATION, 'company_id' => $cId, 'location' => 'company'];
      $meeples[] = ['structure' => CONDUIT, 'company_id' => $cId, 'location' => 'company'];
      $meeples[] = ['structure' => POWERHOUSE, 'company_id' => $cId, 'location' => 'company'];
      $meeples[] = ['structure' => JOKER, 'company_id' => $cId, 'location' => 'company'];
    }

    return self::getMany(self::create($meeples));
  }

  /**
   * Generic base query
   */
  public function getFilteredQuery($cId, $location, $structure)
  {
    $query = self::getSelectQuery();
    if ($cId != null) {
      $query = $query->where('company_id', $cId);
    }
    if ($location != null) {
      $query = $query->where('tile_location', $location);
    }
    if ($structure != null) {
      if (is_array($structure)) {
        $query = $query->whereIn('structure', $structure);
      } else {
        $query = $query->where('structure', strpos($structure, '%') === false ? '=' : 'LIKE', $structure);
      }
    }
    return $query;
  }
}
