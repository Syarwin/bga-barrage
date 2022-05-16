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
use BRG\Managers\Scores;
use BRG\Managers\Actions;
use BRG\Managers\Contracts;

trait RoundTrait
{
  /**
   * State function when starting a round
   */
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

  /**
   * Prepare the new round
   */
  function stStartOfRound()
  {
    $round = Globals::incRound();
    Notifications::startNewRound($round);

    // 1. a) Income
    // TODO

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
    // TODO : handle special tech tiles working kind of like adoptive parent :  && !$player->hasAdoptiveAvailable()
    if (!$company->hasAvailableEngineer()) {
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
    /*
    TODO : handle advanced tech tile
    if (!$player->hasFarmerAvailable() && $player->hasAdoptiveAvailable()) {
      $card = PlayerCards::get('A92_AdoptiveParents');
      $node = $card->getStartOfRoundChoice($player);
    }
    */

    // Inserting leaf PLACE_FARMER
    Engine::setup($node, ['order' => 'actionPhase']);
    Engine::proceed();
  }

  /********************************
   ********************************
   ********** FLOW CHOICE *********
   ********************************
   ********************************/
  function argsResolveChoice()
  {
    $company = Companies::getActive();
    $args = [
      'choices' => Engine::getNextChoice($company),
      'allChoices' => Engine::getNextChoice($company, true),
      'previousEngineChoices' => Globals::getEngineChoices(),
    ];
    $this->addArgsAnytimeAction($args, 'resolveChoice');
    return $args;
  }

  function actChooseAction($choiceId)
  {
    $company = Companies::getActive();
    Engine::chooseNode($company, $choiceId);
  }

  public function stResolveStack()
  {
  }

  /*******************************
   ******* CONFIRM / RESTART ******
   ********************************/
  public function argsConfirmTurn()
  {
    $data = [
      'previousEngineChoices' => Globals::getEngineChoices(),
      'automaticAction' => false,
    ];
    $this->addArgsAnytimeAction($data, 'confirmTurn');
    return $data;
  }

  public function stConfirmTurn()
  {
    // Check user preference to bypass if DISABLED is picked
    $pref = Players::getActive()->getPref(\BRG\OPTION_CONFIRM);
    if ($pref == \BRG\OPTION_CONFIRM_DISABLED) {
      $this->actConfirmTurn();
    }
  }

  public function actConfirmTurn()
  {
    self::checkAction('actConfirmTurn');
    Engine::confirm();
  }

  public function actConfirmPartialTurn()
  {
    self::checkAction('actConfirmPartialTurn');
    Engine::confirmPartialTurn();
  }

  public function actRestart()
  {
    self::checkAction('actRestart');
    if (Globals::getEngineChoices() < 1) {
      throw new \BgaVisibleSystemException('No choice to undo');
    }
    Engine::restart();
  }

  /********************************
   ********************************
   ********** END OF TURN *********
   ********************************
   ********************************/
  function stReturnHome()
  {
    Players::returnHome();
    Notifications::returnHome(Farmers::getAllAvailable());
    Globals::setWorkPhase(false);

    // 1) Listen for cards onReturnHome
    $this->checkCardListeners('ReturnHome', ST_PRE_END_OF_TURN);
  }

  function stPreEndOfTurn()
  {
    // Next turn or harvest
    $round = Globals::getRound();
    $harvest = [4, 7, 9, 11, 13, 14];
    if (in_array($turn, $harvest)) {
      $this->checkCardListeners('BeforeHarvest', ST_START_HARVEST);
    } else {
      $this->gamestate->nextState('end');
    }
  }

  function stEndOfTurn()
  {
    if (Globals::isHarvest()) {
      Globals::setSkipHarvest([]);
    }

    Globals::setHarvest(false);
    if (Globals::getTurn() == 14) {
      $this->gamestate->nextState('end');
      return;
    }

    // Pig Breeder
    if (Globals::getTurn() == 12) {
      $card = PlayerCards::getSingle('A165_PigBreeder', false);
      if ($card != null && $card->isPlayed()) {
        $player = $card->getPlayer();
        if ($player->breed(PIG, clienttranslate("Pig breeder's effect"))) {
          // Inserting leaf REORGANIZE
          Engine::setup(
            [
              'pId' => $player->getId(),
              'action' => REORGANIZE,
              'args' => [
                'trigger' => HARVEST,
                'breedTypes' => [PIG => true],
              ],
            ],
            ['state' => ST_BEFORE_START_OF_TURN]
          );
          Engine::proceed();
          return;
        }
      }
    }

    $this->gamestate->nextState('newTurn');
  }

  function stPreEndOfGame()
  {
    $this->checkCardListeners('BeforeEndOfGame', 'stLaunchEndOfGame');
  }

  function stLaunchEndOfGame()
  {
    foreach (PlayerCards::getAllCardsWithMethod('EndOfGame') as $card) {
      $card->onEndOfGame();
    }
    Globals::setTurn(15);
    Globals::setLiveScoring(true);
    Scores::update(true);
    Notifications::seed(Globals::getGameSeed());
    $this->gamestate->jumpToState(\ST_END_GAME);
  }
}
