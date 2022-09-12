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
use BRG\Helpers\FlowConvertor;

trait RoundStartTrait
{
  function stBeforeStartOfRound()
  {
    $skippedPlayers = Players::getAll()
      ->filter(function ($player) {
        return $player->isZombie();
      })
      ->getIds();
    $skipped = Companies::getCorrespondingIds($skippedPlayers);
    Globals::setSkippedCompanies($skipped);

    $this->gamestate->nextState('');
  }

  function stStartOfRound()
  {
    $round = Globals::incRound();
    Notifications::startNewRound($round);

    // 1. a) Income in reverse order
    $this->changePhase('income');
    $order = array_reverse(Companies::getTurnOrder());
    $this->initCustomTurnOrder('incomePhase', 'stIncomePhase', 'stEndOfStartOfRound', false, true, [], $order);
  }

  /**
   * Income phase for each player
   */
  function stIncomePhase()
  {
    $company = Companies::getActive();
    $flow = $company->getIncomesFlow();
    $vp = FlowConvertor::getVp($company->getIncomes());
    Stats::incVpStructures($company, $vp);
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
      $this->changePhase('headstream');
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
    $this->changePhase('');
    // Check whether contracts need to be filled up again or not
    if (Contracts::needRefill()) {
      $contracts = Contracts::refillStacks();
      if (!$contracts->empty()) {
        Notifications::refillStacks($contracts);
      }
    }

    $company = Companies::getActive();
    Globals::setAntonPower('');
    $powerId = Globals::getMahiriPower();
    if ($powerId != '') {
      Globals::setMahiriPower('');
      Notifications::clearMahiri();
      if ($powerId == \XO_GRAZIANO) {
        Map::updateBasinsCapacities();
      }
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
      $this->gamestate->jumpToState(\ST_PRE_AUTOMA_TURN);
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

  function actSkip($auto = false)
  {
    if (!$auto) {
      self::checkAction('actSkip');
    }
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
