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
}
