<?php
namespace BRG\States;
use BRG\Core\Globals;
use BRG\Core\Notifications;
use BRG\Core\Engine;
use BRG\Core\Stats;
use BRG\Managers\Players;
use BRG\Managers\ActionCards;
use BRG\Managers\Meeples;
use BRG\Managers\Farmers;
use BRG\Managers\Scores;
use BRG\Managers\Actions;
use BRG\Managers\PlayerCards;

trait TurnTrait
{
  /**
   * State function when starting a turn
   *  useful to intercept for some cards that happens at that moment
   */
  function stBeforeStartOfTurn()
  {
    // 0) Make children grow up
    $children = Farmers::growChildren();
    if (!empty($children)) {
      Notifications::growChildren($children);
      Notifications::updateHarvestCosts();
    }

    $skipped = Players::getAll()
      ->filter(function ($player) {
        return $player->isZombie();
      })
      ->getIds();
    Globals::setSkippedPlayers($skipped);

    // 1) Listen for cards BeforeStartOfTurn
    $this->checkCardListeners('BeforeStartOfTurn', 'stPreparation');
  }

  /**
   * Prepare the current turn
   */
  function stPreparation()
  {
    $turn = Globals::incTurn();
    Notifications::startNewTurn($turn);

    // a) Reveal new action card
    $card = ActionCards::draw()->first();
    Notifications::revealActionCard($card);

    // b) Fill up accumulation spots
    $resourceIds = ActionCards::accumulate();
    Notifications::accumulate(Meeples::getMany($resourceIds));

    // Change first player and check start of turn trigger
    $firstPlayer = Globals::getFirstPlayer();
    Stats::incFirstPlayer($firstPlayer);
    $this->initCustomDefaultTurnOrder('startOfTurn', 'stStartOfTurn', 'stPreStartofWorkPhase');
  }

  /*
   *  c) Collect players' resources on action cards
   */
  function stStartOfTurn()
  {
    $pId = Players::getActiveId();
    $turn = Globals::getTurn();

    // Get triggered cards
    $event = [
      'type' => 'StartOfTurn',
      'method' => 'StartOfTurn',
      'pId' => $pId,
    ];
    $reaction = PlayerCards::getReaction($event, false);

    // Get meeple to receive
    $resources = Meeples::getResourcesOnCard('turn_' . $turn, $pId);
    foreach ($resources as $id => $res) {
      // If 'x' field is null, that's just a normal RECEIVE action
      if (is_null($res['x'])) {
        $reaction['childs'][] = [
          'action' => RECEIVE,
          'args' => [
            'meeple' => $id,
          ],
        ];
      }
      // Otherwise, 'x' refers to the card that needs to be triggered by that meeple
      else {
        $card = PlayerCards::get($res['x']);
        $reaction['childs'][] = $card->getReceiveFlow($res);
      }
    }

    if (empty($reaction['childs'])) {
      // No reaction => just go to next player
      $this->nextPlayerCustomOrder('startOfTurn');
    } else {
      // Reaction => boot up the Engine
      Engine::setup($reaction, ['method' => 'stClearStartOfTurn']);
      Engine::proceed();
    }
  }

  /**
   * Clear potential meeples that were left on the card by the player
   */
  function stClearStartOfTurn()
  {
    $pId = Players::getActiveId();
    $turn = Globals::getTurn();

    // Delete any remeaning meeples
    $resources = Meeples::getResourcesOnCard('turn_' . $turn, $pId);
    if ($resources->count() > 0) {
      foreach ($resources as $id => $res) {
        Meeples::DB()->delete($id);
      }
      Notifications::silentKill($resources->toArray());
    }

    $this->nextPlayerCustomOrder('startOfTurn');
  }

  function stPreStartofWorkPhase()
  {
    $this->initCustomDefaultTurnOrder('startOfWork', 'stStartofWorkPhase', 'stStartLaborDay');
  }

  function stStartofWorkPhase()
  {
    $pId = Players::getActiveId();
    $turn = Globals::getTurn();

    // Get triggered cards
    $event = [
      'type' => 'startOfWork',
      'method' => 'startOfWork',
      'pId' => $pId,
    ];
    $reaction = PlayerCards::getReaction($event, false);

    if (empty($reaction['childs'])) {
      // No reaction => just go to next player
      $this->nextPlayerCustomOrder('startOfWork');
    } else {
      // Reaction => boot up the Engine
      Engine::setup($reaction, ['order' => 'startOfWork']);
      Engine::proceed();
    }
  }

  function stStartLaborDay()
  {
    Globals::setObtainedResourcesDuringWork([]);
    Globals::setWorkPhase(true);

    // Change first player and start labor
    $this->initCustomDefaultTurnOrder('labor', ST_LABOR, ST_RETURNING_HOME, true);
  }

  /**
   * Activate next player with a farmer available
   */
  function stLabor()
  {
    $player = Players::getActive();

    // Already out of round ? => Go to the next player if one is left
    $skipped = Globals::getSkippedPlayers();
    if (in_array($player->getId(), $skipped)) {
      // Everyone is out of round => end it
      $remaining = array_diff(Players::getAll()->getIds(), $skipped);
      if (empty($remaining)) {
        $this->endCustomOrder('labor');
      } else {
        $this->nextPlayerCustomOrder('labor');
      }
      return;
    }

    // No farmer to allocate ?
    if (!$player->hasFarmerAvailable() && !$player->hasAdoptiveAvailable()) {
      $skipped[] = $player->getId();
      Globals::setSkippedPlayers($skipped);
      $this->nextPlayerCustomOrder('labor');
      return;
    }

    self::giveExtraTime($player->getId());

    $args = [];
    PlayerCards::applyEffects($player, 'resetFlags', $args);

    $node = [
      'action' => PLACE_FARMER,
      'pId' => $player->getId(),
    ];
    if (!$player->hasFarmerAvailable() && $player->hasAdoptiveAvailable()) {
      $card = PlayerCards::get('A92_AdoptiveParents');
      $node = $card->getStartOfRoundChoice($player);
    }

    // Inserting leaf PLACE_FARMER
    Engine::setup($node, ['order' => 'labor']);
    Engine::proceed();
  }

  /********************************
   ********************************
   ********** FLOW CHOICE *********
   ********************************
   ********************************/
  function argsResolveChoice()
  {
    $player = Players::getActive();
    $args = [
      'choices' => Engine::getNextChoice($player),
      'allChoices' => Engine::getNextChoice($player, true),
      'previousEngineChoices' => Globals::getEngineChoices(),
    ];
    $this->addArgsAnytimeAction($args, 'resolveChoice');
    return $args;
  }

  function actChooseAction($choiceId)
  {
    $player = Players::getActive();
    Engine::chooseNode($player, $choiceId);
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
    $pref = Players::getActive()->getPref(OPTION_CONFIRM);
    if ($pref == OPTION_CONFIRM_DISABLED) {
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
    $turn = Globals::getTurn();
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
