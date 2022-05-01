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
    if ($row['id'] < 100) {
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

  /**
   * Generic base query
   */
  public function getFilteredQuery($cId, $location, $type)
  {
    $query = self::getSelectQuery();
    if ($cId != null) {
      $query = $query->where('company_id', $cId);
    }
    if ($location != null) {
      $query = $query->where('meeple_location', $location);
    }
    if ($type != null) {
      if (is_array($type)) {
        $query = $query->whereIn('type', $type);
      } else {
        $query = $query->where('type', strpos($type, '%') === false ? '=' : 'LIKE', $type);
      }
    }
    return $query;
  }
}
