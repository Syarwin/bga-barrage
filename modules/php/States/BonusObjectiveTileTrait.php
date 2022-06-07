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

    return $bonuses;
  }

  ////////////////////////////////////////////////////////////
  //  ____                          _____ _ _
  // | __ )  ___  _ __  _   _ ___  |_   _(_) | ___  ___
  // |  _ \ / _ \| '_ \| | | / __|   | | | | |/ _ \/ __|
  // | |_) | (_) | | | | |_| \__ \   | | | | |  __/\__ \
  // |____/ \___/|_| |_|\__,_|___/   |_| |_|_|\___||___/
  ////////////////////////////////////////////////////////////

  public function computeRoundReward($company, $round = null)
  {
    $round = $round ?? Globals::getRound();
    $bonusTile = Globals::getBonusTiles()[$round - 1];

    switch ($bonusTile) {
      case BONUS_CONTRACT:
        return count($company->getContracts(true)) * 2;
      case BONUS_BASE:
        return 4 * $company->countBuiltStructures(BASE);
      case BONUS_ELEVATION:
        return 4 * $company->countBuiltStructures(ELEVATION);
      case BONUS_CONDUIT:
        return 4 * $company->countBuiltStructures(CONDUIT);
      case BONUS_POWERHOUSE:
        return 5 * $company->countBuiltStructures(POWERHOUSE);
      case BONUS_ADVANCED_TILE:
        //TODO bonus advancer
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

  public function computeRoundBonus($company, $round = null)
  {
    $energy = $company->getEnergy();
    if ($energy < 6) {
      return null;
    }

    $necessaryEnergy = Globals::getRound() * 6;
    $bonus = $this->computeRoundReward($company, $round);
    $malus = max(0, ceil(($necessaryEnergy - $energy) / 6) * 4);
    $vp = $bonus - $malus;
    return max($vp, 0);
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
        $slots = Map::getConstructSlots();
        $locations = [];
        foreach ($slots as $slot) {
          if (($slot['cost'] ?? 0) > 0) {
            $locations[] = $slot['id'];
          }
        }

        return $company->countBuiltStructures([BASE, POWERHOUSE], $locations);

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

      // TODO : share VP in case of tie
      foreach ($companies as $cId) {
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
      }
      $n++;
    }
    // TODO : stats
    return $flow;
  }
}