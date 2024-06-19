<?php

namespace BRG\Managers;

use BRG\Helpers\Utils;
use BRG\Helpers\Collection;
use BRG\Core\Globals;
use BRG\Core\Notifications;

/* Class to manage all the external works for Barrage LWP */

class ExternalWorks extends \BRG\Helpers\Pieces
{
  protected static $table = 'works';
  protected static $prefix = 'work_';
  protected static $autoIncrement = false;
  protected static $autoremovePrefix = false;

  protected static function cast($row)
  {
    $data = self::getWorks()[$row['work_id']];
    return new \BRG\Models\ExternalWork($row, $data);
  }

  public static function getUiData()
  {
    return Globals::isLWP()
      ? self::getSelectQuery()
      ->whereNotIn('work_location', ['deckA', 'deckB', 'deckC'])
      ->get()
      ->toArray()
      : [];
  }

  public static function getFilteredQuery($cId, $location = null)
  {
    $query = self::getSelectQuery();
    if ($cId != null) {
      $query = $query->where('company_id', $cId);
    }
    if ($location != null) {
      $query = $query->where('work_location', strpos($location, '%') === false ? '=' : 'LIKE', $location);
    }
    return $query;
  }

  /* Creation of various external works */
  public static function setupNewGame()
  {
    if (!Globals::isLWP()) {
      return;
    }

    // Create them
    $works = [];
    foreach (self::getWorks() as $id => $work) {
      $works[] = [
        'id' => $id,
        'location' => 'deck' . chr(65 + floor($id / 100)),
      ];
    }

    self::create($works);

    for ($i = 0; $i < 3; $i++) {
      // shuffle each deck
      self::shuffle('deck' . chr(65 + $i));

      // pick the external work
      self::pickForLocation(1, 'deckA', 'work_' . ($i + 1));
    }
  }

  public static function newRound()
  {
    // discard all tiles
    $tiles = self::getFilteredQuery(null, 'work_%')->get();
    $deleted = [];
    foreach ($tiles as $tId => $tile) {
      $deleted[] = $tId;
      self::DB()->delete($tId);
    }

    if (count($deleted) > 0) {
      Notifications::discardWorks($deleted);
    }

    // draw new ones
    $created = new Collection();
    $deck = 0;
    for ($i = 1; $i <= 3; $i++) {
      $created = $created->merge(self::pickForLocation(1, 'deck' . chr(65 + $deck), 'work_' . $i, null, false));
      if (count($created) < $i) {
        $deck++;
        $created = $created->merge(self::pickForLocation(1, 'deck' . chr(65 + $deck), 'work_' . $i, null, false) ?? []);
        if (count($created) < $i) {
          $deck++;
          $created = $created->merge(
            self::pickForLocation(1, 'deck' . chr(65 + $deck), 'work_' . $i, null, false) ?? []
          );
        }
      }
    }
    Notifications::refillExternalWorks($created);
  }

  public static function getWorks()
  {
    $f = function ($machineCost, $reward) {
      return self::format($machineCost, $reward);
    };

    return [
      //     _
      //    / \
      //   / _ \
      //  / ___ \
      // /_/   \_\
      //
      1 => $f([\EXCAVATOR => 1], [CREDIT => 3, FULFILL_CONTRACT => 4]),
      2 => $f([\MIXER => 1], [CREDIT => 3, ROTATE_WHEEL => 2]),
      3 => $f([\MIXER => 2], [VP => 4, BASE => [\PLAIN]]),
      4 => $f([\MIXER => 1, \EXCAVATOR => 1], [CREDIT => 2, \POWERHOUSE => 1]),
      5 => $f([\EXCAVATOR => 2], [CREDIT => 4, ADVANCED_TECH_TILE => null]),
      //  ____
      // | __ )
      // |  _ \
      // | |_) |
      // |____/
      //

      100 => $f([\EXCAVATOR => 2, MIXER => 1], [CREDIT => 5, ENERGY_PRODUCED => 6]),
      101 => $f([MIXER => 4], [VP => 2, ELEVATION => 2]),
      102 => $f([MIXER => 3], [CREDIT => 3, CONDUIT => 4, VP => 3]),
      103 => $f([EXCAVATOR => 3], [VP => 5, ENERGY => 6]),
      104 => $f([EXCAVATOR => 4], [VP => 4, \PLACE_DROPLET => 2, \ROTATE_WHEEL => 2]),
      //   ____
      //  / ___|
      // | |
      // | |___
      //  \____|
      //

      200 => $f([\EXCAVATOR => 2, MIXER => 4], [VP => 8, ROTATE_WHEEL => 3]),
      201 => $f([MIXER => 5], [VP => 10, \FLOW_DROPLET => 1]),
      202 => $f([EXCAVATOR => 6], [VP => 5, \FULFILL_CONTRACT => -1]),
      203 => $f([\EXCAVATOR => 3, MIXER => 2], [VP => 6, \FLOW_DROPLET => 2]),
      204 => $f([EXCAVATOR => 5], [VP => 6, MIXER => 3]),
    ];
  }

  /////////////////////////
  //  _   _ _   _ _
  // | | | | |_(_) |___
  // | | | | __| | / __|
  // | |_| | |_| | \__ \
  //  \___/ \__|_|_|___/
  /////////////////////////
  private static function format($machineCost, $reward)
  {
    return [
      'cost' => $machineCost,
      'reward' => $reward,
    ];
  }
}
