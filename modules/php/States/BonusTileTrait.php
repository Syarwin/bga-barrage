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
          break;
        case OBJECTIVE_BASIN_THREE:
          break;
      }
    }
  }
}
