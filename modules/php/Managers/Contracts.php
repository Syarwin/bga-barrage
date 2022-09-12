<?php
namespace BRG\Managers;
use BRG\Helpers\Utils;
use BRG\Helpers\Collection;

/* Class to manage all the contracts for Barrage */

class Contracts extends \BRG\Helpers\Pieces
{
  protected static $table = 'contracts';
  protected static $prefix = 'contract_';
  protected static $autoIncrement = false;
  protected static $autoremovePrefix = false;
  protected static $customFields = ['type'];

  protected static function cast($row)
  {
    $data = self::getContracts()[$row['contract_id']];
    return new \BRG\Models\Contract($row, $data);
  }

  public static function getUiData()
  {
    // Visible contracts
    $data = [
      'board' => self::getSelectQuery()
        ->where('contract_location', '<>', 'box')
        ->get()
        ->toArray(),
      'stacks' => [],
    ];

    // Contracts in the stack
    for ($i = 2; $i <= 4; $i++) {
      $data['stacks'][$i] = self::getSelectQuery()
        ->where('type', $i)
        ->where('contract_location', 'box')
        ->count();
    }

    return $data;
  }

  /* Creation of various contracts */
  public static function setupNewGame()
  {
    // Create them
    $contracts = [];
    foreach (self::getContracts() as $id => $contract) {
      $contracts[] = [
        'id' => $id,
        'type' => floor($id / 100),
        'location' => 'box',
      ];
    }

    self::create($contracts);

    // Draw national contracts
    $n = Companies::count() - 1;
    $nationals = self::getSelectQuery()
      ->where('type', 1)
      ->get()
      ->getIds();
    self::move(Utils::rand($nationals, $n), 'contract-stack-1');

    // Draw green/yellow/red
    for ($i = 2; $i < 5; $i++) {
      $contracts = self::getSelectQuery()
        ->where('type', $i)
        ->get()
        ->getIds();
      self::move(Utils::rand($contracts, 2), 'contract-stack-' . $i);
    }
  }

  public function randomStartingPick($nPlayers)
  {
    $contractIds = Utils::rand(STARTING_CONTRACTS, $nPlayers);
    self::move($contractIds, 'pickStart');
  }

  public function getNationalContracts()
  {
    return self::getSelectQuery()
      ->where('contract_location', 'LIKE', 'contract-stack%')
      ->where('type', '1')
      ->get();
  }

  public function getAvailableToTake($type = null)
  {
    $query = self::getSelectQuery()->where('contract_location', 'LIKE', 'contract-stack%');
    if (is_null($type)) {
      $query = $query->where('type', '<>', '1');
    } else {
      $query = $query->where('type', $type);
    }

    return $query->get();
  }

  public function getStartingPick()
  {
    return self::getInLocation('pickStart');
  }

  public function needRefill()
  {
    return self::getAvailableToTake()->count() < 6;
  }

  public function refillStacks()
  {
    $moved = [];
    for ($i = 2; $i <= 4; $i++) {
      $n = self::getAvailableToTake($i)->count();
      if ($n < 2) {
        $contractIds = self::getSelectQuery()
          ->where('contract_location', 'box')
          ->where('type', $i)
          ->get()
          ->getIds();
        $toPick = min(count($contractIds), 2 - $n);
        if ($toPick > 0) {
          $contractIds = Utils::rand($contractIds, $toPick);
          self::move($contractIds, 'contract-stack-' . $i);
          $moved = array_merge($moved, $contractIds);
        }
      }
    }

    return self::getMany($moved);
  }

  public function getContracts()
  {
    $f = function ($energyCost, $reward) {
      return self::format($energyCost, $reward);
    };

    return [
      //  ____  _             _   _
      // / ___|| |_ __ _ _ __| |_(_)_ __   __ _
      // \___ \| __/ _` | '__| __| | '_ \ / _` |
      //  ___) | || (_| | |  | |_| | | | | (_| |
      // |____/ \__\__,_|_|   \__|_|_| |_|\__, |
      //                                  |___/
      1 => $f(3, [CREDIT => 2, ENERGY => 2]),
      2 => $f(3, [CREDIT => 2, ROTATE_WHEEL => 1]),
      3 => $f(2, [CREDIT => 3]),
      4 => $f(4, [CREDIT => 2, ANY_MACHINE => 1]),
      //      5 => $f(4, [FLOW_DROPLET => 1, CREDIT => 3]),

      //  _   _       _   _                   _
      // | \ | | __ _| |_(_) ___  _ __   __ _| |
      // |  \| |/ _` | __| |/ _ \| '_ \ / _` | |
      // | |\  | (_| | |_| | (_) | | | | (_| | |
      // |_| \_|\__,_|\__|_|\___/|_| |_|\__,_|_|
      //
      100 => $f(13, [VP => 7, ROTATE_WHEEL => 4]),
      101 => $f(13, [VP => 6, ANY_MACHINE => 4]),
      102 => $f(14, [VP => 8, CREDIT => 8]),
      103 => $f(14, [VP => 8, ENERGY => 6]),
      104 => $f(15, [VP => 12]),
      105 => $f(15, [VP => 9, FLOW_DROPLET => 3]),

      //   ____
      //  / ___|_ __ ___  ___ _ __
      // | |  _| '__/ _ \/ _ \ '_ \
      // | |_| | | |  __/  __/ | | |
      //  \____|_|  \___|\___|_| |_|
      //
      200 => $f(2, [CREDIT => 1, ROTATE_WHEEL => 2]),
      201 => $f(2, [ANY_MACHINE => 2]),
      202 => $f(2, [CREDIT => 5]),
      203 => $f(2, [VP => 2, ENERGY => 3]),
      204 => $f(2, [CREDIT => 3, PLACE_DROPLET => 2]),
      205 => $f(3, [CREDIT => 4, EXCAVATOR => 1]),
      206 => $f(3, [VP => 3, MIXER => 1]),
      207 => $f(3, [CREDIT => 4, FLOW_DROPLET => 1]),
      208 => $f(3, [ENERGY => 2, ROTATE_WHEEL => 2]),
      209 => $f(3, [VP => 4, CREDIT => 2]),
      210 => $f(4, [CONDUIT => 2]),
      211 => $f(4, [BASE => [PLAIN]]),
      212 => $f(4, [VP => 5, EXCAVATOR => 1]),
      213 => $f(4, [VP => 1, ROTATE_WHEEL => 3]),
      214 => $f(4, [VP => 3, FLOW_DROPLET => 2]),

      // __   __   _ _
      // \ \ / /__| | | _____      __
      //  \ V / _ \ | |/ _ \ \ /\ / /
      //   | |  __/ | | (_) \ V  V /
      //   |_|\___|_|_|\___/ \_/\_/
      //

      300 => $f(5, [VP => 4, EXCAVATOR => 2]),
      301 => $f(5, [POWERHOUSE => 1]),
      302 => $f(5, [CREDIT => 3, MIXER => 2]),
      303 => $f(5, [VP => 2, FLOW_DROPLET => 1, ROTATE_WHEEL => 2]),
      304 => $f(5, [VP => 3, EXCAVATOR => 1, ENERGY => 2]),
      305 => $f(6, [BASE => [PLAIN, HILL]]),
      306 => $f(6, [ROTATE_WHEEL => 3, FLOW_DROPLET => 1]),
      307 => $f(6, [CONDUIT => 3]),
      308 => $f(6, [VP => 3, ANY_MACHINE => 2]),
      309 => $f(6, [VP => 2, ELEVATION => 1]),
      310 => $f(7, [VP => 6, EXCAVATOR => 1]),
      311 => $f(7, [VP => 7, ROTATE_WHEEL => 1]),
      312 => $f(7, [VP => 3, FLOW_DROPLET => 2, MIXER => 1]),
      313 => $f(7, [VP => 6, PLACE_DROPLET => 3]),
      314 => $f(7, [CREDIT => 4, PLACE_DROPLET => 2, ANY_MACHINE => 1]),

      //  ____          _
      // |  _ \ ___  __| |
      // | |_) / _ \/ _` |
      // |  _ <  __/ (_| |
      // |_| \_\___|\__,_|
      //
      400 => $f(8, [VP => 4, ROTATE_WHEEL => 3]),
      401 => $f(8, [BASE => 1]),
      402 => $f(8, [CREDIT => 4, MIXER => 1, \ROTATE_WHEEL => 2]),
      403 => $f(8, [CONDUIT => 4]),
      404 => $f(8, [VP => 4, ENERGY => 5]),
      405 => $f(9, [VP => 3, CREDIT => 2, ANY_MACHINE => 2]),
      406 => $f(9, [VP => 8, ROTATE_WHEEL => 1]),
      407 => $f(9, [VP => 5, PLACE_DROPLET => 3, EXCAVATOR => 1]),
      408 => $f(9, [POWERHOUSE => 1, ENERGY => 3]),
      409 => $f(9, [ELEVATION => 1, FLOW_DROPLET => 2]),
      410 => $f(10, [CONDUIT => 5]),
      411 => $f(10, [VP => 7, CREDIT => 3]),
      412 => $f(10, [VP => 8, FLOW_DROPLET => 1]),
      413 => $f(10, [VP => 6, ROTATE_WHEEL => 2, PLACE_DROPLET => 2]),
      414 => $f(10, [VP => 2, ANY_MACHINE => 3]),
    ];
  }

  /////////////////////////
  //  _   _ _   _ _
  // | | | | |_(_) |___
  // | | | | __| | / __|
  // | |_| | |_| | \__ \
  //  \___/ \__|_|_|___/
  /////////////////////////
  private function format($energyCost, $reward)
  {
    return [
      'cost' => $energyCost,
      'reward' => $reward,
    ];
  }
}
