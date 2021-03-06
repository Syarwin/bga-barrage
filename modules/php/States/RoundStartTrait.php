<?php
namespace BRG\States;
use BRG\Map;
use BRG\Core\Globals;
use BRG\Core\Notifications;
use BRG\Core\Engine;
use BRG\Core\Stats;
use BRG\Managers\Players;
use BRG\Managers\Companies;
use BRG\Managers\Meeples;
use BRG\Managers\Actions;
use BRG\Managers\Contracts;

trait RoundStartTrait
{
  // TODO : these two can be merged unless a XO power happends in between ?
  function stBeforeStartOfRound()
  {
    //TODO
    $skipped = [];
    /*
    $skipped = Players::getAll()
      ->filter(function ($player) {
        return $player->isZombie();
      })
      ->getIds();
      */
    Globals::setSkippedCompanies($skipped);

    $this->gamestate->nextState('');
  }

  function stStartOfRound()
  {
    $round = Globals::incRound();
    Notifications::startNewRound($round);

    // 1. a) Income
    $this->initCustomTurnOrder('incomePhase', 'stIncomePhase', 'stEndOfStartOfRound');
  }

  /**
   * Income phase for each player
   */
  function stIncomePhase()
  {
    $company = Companies::getActive();
    $flow = $company->getIncomesFlow();
    if (empty($flow)) {
      $this->nextPlayerCustomOrder('incomePhase');
    } else {
      Engine::setup($flow, ['order' => 'incomePhase']);
      Engine::proceed();
    }
  }

  /**
   * Prepare the new round
   */
  function stEndOfStartOfRound()
  {
    $round = Globals::getRound();
    // 1. b) Headstreams
    if ($round < 5) {
      $droplets = Map::fillHeadstreams();
      Notifications::fillHeadstreams($droplets);
    }

    // Change first player and start action phase (with loop = true)
    $this->initCustomDefaultTurnOrder('actionPhase', ST_ACTION_PHASE, ST_RETURNING_HOME, true);
  }

  /**
   * Activate next player with a farmer available
   */
  function stActionPhase()
  {
    // Check whether contracts need to be filled up again or not
    if (Contracts::needRefill()) {
      $contracts = Contracts::refillStacks();
      if (!$contracts->empty()) {
        Notifications::refillStacks($contracts);
      }
    }

    $company = Companies::getActive();
    Globals::setAntonPower('');
    if (Globals::getMahiriPower() != '') {
      Globals::setMahiriPower('');
      Notifications::clearMahiri();
    }

    // Already out of round ? => Go to the next company if one is left
    $skipped = Globals::getSkippedCompanies();
    if (in_array($company->getId(), $skipped)) {
      // Everyone is out of round => end it
      $remaining = array_diff(Companies::getAll()->getIds(), $skipped);
      if (empty($remaining)) {
        $this->endCustomOrder('actionPhase');
      } else {
        $this->nextPlayerCustomOrder('actionPhase');
      }
      return;
    }

    // No engineer to allocate ?
    if (!$company->hasAvailableEngineer() && !$company->hasEngineerFreeTiles()) {
      $skipped[] = $company->getId();
      Globals::setSkippedCompanies($skipped);
      $this->nextPlayerCustomOrder('actionPhase');
      return;
    }

    if ($company->isAI()) {
      // TODO : handle AI
      die('AI not implemented yet !');
      return;
    }

    // Give extra time
    self::giveExtraTime($company->getPId());

    // TODO : reset some flags ?
    //$args = [];
    //PlayerCards::applyEffects($player, 'resetFlags', $args);

    $node = [
      'action' => PLACE_ENGINEER,
      'cId' => $company->getId(),
    ];

    // Inserting leaf PLACE_ENGINEER
    Engine::setup($node, ['order' => 'actionPhase']);
    Engine::proceed();
  }

  function actSkip()
  {
    self::checkAction('actSkip');
    $company = Companies::getActive();
    if ($company->hasAvailableEngineer()) {
      throw new \BgaUserException(clienttranslate('You cannot skip your turn, you have remaining engineers'));
    }

    $skipped = Globals::getSkippedCompanies();
    $skipped[] = $company->getId();
    Globals::setSkippedCompanies($skipped);
    Notifications::message(clienttranslate('${company_name} skips his turn'), ['company' => $company]);
    $this->nextPlayerCustomOrder('actionPhase');
    return;
  }
}
