<?php
namespace BRG\States;
use BRG\Core\Globals;
use BRG\Managers\Players;
use BRG\Managers\Companies;
use BRG\Managers\Meeples;
use BRG\Managers\Fences;
use BRG\Managers\Actions;
use BRG\Core\Engine;
use BRG\Core\Game;
use BRG\Models\PlayerBoard;
use BRG\Core\Notifications;
use BRG\Map;

trait BonusObjectiveTileTrait
{
  public function computeBonuses()
  {
    $bonuses = [];
    foreach (Companies::getAll() as $cId => $company) {
      $bonuses[$cId] = [
        'round' => $this->computeRoundBonus($company),
        'obj' => $this->computeObjectiveQuantity($company),
      ];
    }

    $bonuses['objTile'] = $this->computeObjectiveTileBonuses();

    return $bonuses;
  }

  ////////////////////////////////////////////////////////////
  //  ____                          _____ _ _
  // | __ )  ___  _ __  _   _ ___  |_   _(_) | ___  ___
  // |  _ \ / _ \| '_ \| | | / __|   | | | | |/ _ \/ __|
  // | |_) | (_) | | | | |_| \__ \   | | | | |  __/\__ \
  // |____/ \___/|_| |_|\__,_|___/   |_| |_|_|\___||___/
  ////////////////////////////////////////////////////////////

  public function computeRoundBonusQuantity($company, $round = null)
  {
    $round = $round ?? Globals::getRound();
    $bonusTile = Globals::getBonusTiles()[$round - 1];

    switch ($bonusTile) {
      case BONUS_CONTRACT:
        return $company->getFulfilledContracts()->count();
      case BONUS_BASE:
        return $company->countBuiltStructures(BASE);
      case BONUS_ELEVATION:
        return $company->countBuiltStructures(ELEVATION);
      case BONUS_CONDUIT:
        return $company->countBuiltStructures(CONDUIT);
      case BONUS_POWERHOUSE:
        return $company->countBuiltStructures(POWERHOUSE);
      case BONUS_ADVANCED_TILE:
        return $company->countAdvancedTiles();
        break;
      case BONUS_EXTERNAL_WORK:
        // TODO LWP
        break;
      case BONUS_BUILDING:
        // TODO LWP
        break;
    }
    throw new \feException('bonus tile not implemnted');
  }

  public function getRoundBonusMultCoeff($round = null)
  {
    $bonusTile = Globals::getBonusTiles()[$round - 1];
    $multiplicativeCoeffs = [
      BONUS_CONTRACT => 2,
      BONUS_BASE => 4,
      BONUS_ELEVATION => 4,
      BONUS_CONDUIT => 4,
      BONUS_POWERHOUSE => 5,
      BONUS_ADVANCED_TILE => 4,
    ];
    return $multiplicativeCoeffs[$bonusTile];
  }

  public function computeRoundBonus($company, $round = null)
  {
    $round = max(1, $round ?? Globals::getRound());
    $energy = $company->getEnergy();
    $necessaryEnergy = $round * 6;

    $datas = [];
    // Compute number of stuff
    $datas['n'] = $this->computeRoundBonusQuantity($company, $round);
    // Compute bonus
    $datas['mult'] = $this->getRoundBonusMultCoeff($round);
    $datas['bonus'] = $datas['n'] * $datas['mult'];
    // Add malus
    $datas['malus'] = max(0, ceil(($necessaryEnergy - $energy) / 6) * 4);
    // Total
    $datas['vp'] = $energy < 6 ? null : max(0, $datas['bonus'] - $datas['malus']);

    return $datas;
  }

  /////////////////////////////////////////////////////////////////
  //   ___  _     _           _   _             _____ _ _
  //  / _ \| |__ (_) ___  ___| |_(_)_   _____  |_   _(_) | ___
  // | | | | '_ \| |/ _ \/ __| __| \ \ / / _ \   | | | | |/ _ \
  // | |_| | |_) | |  __/ (__| |_| |\ V /  __/   | | | | |  __/
  //  \___/|_.__// |\___|\___|\__|_| \_/ \___|   |_| |_|_|\___|
  //           |__/
  /////////////////////////////////////////////////////////////////
  public function computeObjectiveQuantity($company)
  {
    $map = Map::getZones();
    switch (Globals::getObjectiveTile()) {
      case OBJECTIVE_PAYING_SLOT:
        // Compute paying spaces
        $slots = Map::getConstructSlots(true);
        $locations = [];
        foreach ($slots as $slot) {
          if (($slot['cost'] ?? 0) > 0) {
            $locations[] = $slot['id'];
          }
        }
        return $company->countBuiltStructures([POWERHOUSE, BASE], $locations);

      case OBJECTIVE_MOST_STRUCTURE:
        $max = 0;
        foreach (AREAS as $area) {
          $nBuilt = $company->countBuiltStructures(STRUCTURES, Map::getLocationsInArea($area));
          $max = max($nBuilt, $max);
        }
        return $max;

      case OBJECTIVE_LEAST_STRUCTURE:
        $min = 999;
        foreach (AREAS as $area) {
          $nBuilt = $company->countBuiltStructures(STRUCTURES, Map::getLocationsInArea($area));
          $min = min($nBuilt, $min);
        }
        return $min;

      case OBJECTIVE_CONNECTIONS:
        return count(Map::getProductionSystems($company, 0, null, true));

      case OBJECTIVE_BASIN_ONE:
        $count = 0;
        foreach ($map as $zId => $zone) {
          if ($company->countBuiltStructures(STRUCTURES, Map::getLocationsInZone($zId)) >= 1) {
            $count++;
          }
        }
        return $count;

      case OBJECTIVE_BASIN_THREE:
        $count = 0;
        foreach ($map as $zId => $zone) {
          if ($company->countBuiltStructures(STRUCTURES, Map::getLocationsInZone($zId)) >= 3) {
            $count++;
          }
        }
        return $count;
    }
  }

  public function computeObjectiveTileBonuses()
  {
    // Compute quantities and order companies according to that
    $qqty = [];
    foreach (Companies::getAll() as $cId => $company) {
      $qqty[$this->computeObjectiveQuantity($company)][] = $cId;
    }
    krsort($qqty);

    // Distribute VP
    $n = 1;
    $scoreMap = [1 => 15, 2 => 10, 3 => 5];
    $bonuses = [];
    foreach ($qqty as $amount => $companies) {
      if ($n > 3) {
        break;
      }

      // Compute the number of VP to share
      $vp = 0;
      $positions = [];
      foreach ($companies as $cId) {
        if ($n <= 3) {
          $positions[] = $n;
          $vp += $scoreMap[$n];
          $n++;
        }
      }

      // Compute the share
      $share = ceil($vp / count($companies));

      $bonuses[] = [
        'pos' => $positions,
        'vp' => $vp,
        'cIds' => $companies,
        'share' => $share,
      ];
    }

    return $bonuses;
  }
}

/*
/*
$bonuses[] = [$cId, $n, ]
$flow[] = [
  'action' => GAIN,
  'source' =>
    $n == 1
      ? clienttranslate('1st place on objective tile')
      : ($n == 2
        ? clienttranslate('2nd place on objective tile')
        : clienttranslate('3rd place on objective tile')),
  'args' => ['cId' => $cId, VP => ceil($scoreMap[$n] / count($companies))],
];
*/
