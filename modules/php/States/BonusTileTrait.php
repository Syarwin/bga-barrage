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

trait BonusTileTrait
{
  public function calculateRoundBonus($companyId)
  {
    $round = Globals::getRound();
    $bonusTile = Globals::getBonusTiles()[$round - 1];
    $company = Companies::get($companyId);

    switch ($bonusTile) {
      case BONUS_CONTRACT:
        return count($company->getContracts(true)) * 2;
        break;
      case BONUS_BASE:
        return Meeples::getSelectQuery()
          ->where('company_id', $companyId)
          ->whereNotIn('meeple_location', ['company'])
          ->where('type', BASE)
          ->count() * 4;
        break;
      case BONUS_ELEVATION:
        return Meeples::getSelectQuery()
          ->where('company_id', $companyId)
          ->whereNotIn('meeple_location', ['company'])
          ->where('type', \ELEVATION)
          ->count() * 4;
        break;
      case BONUS_CONDUIT:
        return Meeples::getSelectQuery()
          ->where('company_id', $companyId)
          ->whereNotIn('meeple_location', ['company'])
          ->where('type', CONDUIT)
          ->count() * 4;
        break;
      case BONUS_POWERHOUSE:
        return Meeples::getSelectQuery()
          ->where('company_id', $companyId)
          ->whereNotIn('meeple_location', ['company'])
          ->where('type', POWERHOUSE)
          ->count() * 4;
        break;
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

  public function calculateObjectiveTile()
  {
    $tile = Globals::getObjectiveTile();
    $score = [];
    $map = Map::getZones();
    foreach (Companies::getAll() as $cId => $company) {
      switch ($tile) {
        case OBJECTIVE_PAYING_SLOT:
          $c = 0;
          $meeples = Meeples::getFilteredQuery($cId, null, [BASE, POWERHOUSE])
            ->whereNotIn('meeple_location', ['company'])
            ->get();
          foreach ($meeples as $mId => $m) {
            if ($m['type'] == BASE && substr($m['location'], -1) == 'U') {
              $c++;
            }
            if ($m['type'] == POWERHOUSE) {
              $zone = substr(explode('_', $m['location'])[0], 1);
              $pow = explode('_', $m['location'])[1];
              $cost = $map[$zone]['powerhouses'][$pow] ?? 0;
              if ($cost == 3) {
                $c++;
              }
            }
          }
          $score[$c][] = $cId;
          break;
        case OBJECTIVE_MOST_STRUCTURE:
          $max = 0;
          foreach ($map as $zId => $zone) {
            $c = Meeples::getFilteredQuery($cId, Map::getLocationsInZone($zId), [
              BASE,
              ELEVATION,
              CONDUIT,
              POWERHOUSE,
            ])->count();
            $max = max($c, $max);
          }
          $score[$max][] = $cId;
          break;
        case OBJECTIVE_CONNECTIONS:
          $c = 0;
          foreach (Map::getZones() as $zoneId => $zone) {
            // Compute the possible conduits
            $conduits = [];
            foreach ($zone['conduits'] ?? [] as $sId => $conduit) {
              // Is this conduit built by someone ?
              $meeple = Meeples::getOnSpace($sId, CONDUIT, $cId)->first();
              if (is_null($meeple)) {
                continue;
              }

              // Is it linked to a powerhouse built by the company ?
              $endingSpace = 'P' . $conduit['end'] . '%'; // Any powerhouse in the ending zone
              $powerhouse = Meeples::getOnSpace($endingSpace, POWERHOUSE, $company)->first();
              if (is_null($powerhouse)) {
                continue;
              }

              $c += count(Meeples::getOnSpace($zone['basins'] ?? [], BASE));
            }
          }
          $score[$c][] = $cId;
          break;
        case OBJECTIVE_LEAST_STRUCTURE:
          $max = 999;
          foreach ($map as $zId => $zone) {
            $c = Meeples::getFilteredQuery($cId, Map::getLocationsInZone($zId), [
              BASE,
              ELEVATION,
              CONDUIT,
              POWERHOUSE,
            ])->count();
            $max = min($c, $max);
          }
          $score[$max * -1][] = $cId;
          break;
        case OBJECTIVE_BASIN_ONE:
          $count = 0;
          foreach ($map as $zId => $zone) {
            $c = Meeples::getFilteredQuery($cId, Map::getLocationsInZone($zId), [
              BASE,
              ELEVATION,
              CONDUIT,
              POWERHOUSE,
            ])->count();
            $count += $c;
          }
          $score[$count][] = $cId;
          break;
        case OBJECTIVE_BASIN_THREE:
          $count = 0;
          foreach ($map as $zId => $zone) {
            $c = Meeples::getFilteredQuery($cId, Map::getLocationsInZone($zId), [
              BASE,
              ELEVATION,
              CONDUIT,
              POWERHOUSE,
            ])->count();
            $count += $c;
          }
          $score[$count][] = $cId;
          break;
      }
    }

    krsort($score);
    $flow = [];
    $n = 1;
    $scoreMap = [1 => 15, 2 => 10, 3 => 5];

    // if egality, split of the VP
    foreach ($score as $amount => $companies) {
      if ($n > 3) {
        break;
      }

      foreach ($companies as $cId) {
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
      }
      $n++;
    }
    // TODO : stats
    return $flow;
  }
}
