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
    $this->initCustomTurnOrder('incomePhase', 'stIncomePhase', 'stEndOfStartOfRound');
  }

  function stIncomePhase()
  {
    $company = Companies::getActive();
    $income = $company->earnIncome();
    if (empty($income)) {
      $this->nextPlayerCustomOrder('incomePhase');
    } else {
      Engine::setup($income, ['order' => 'incomePhase']);
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

    // Inserting leaf PLACE_ENGINEER
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
    $creditMap = [
      0 => 3,
      1 => 1,
      2 => 2,
      3 => 2,
      4 => 3,
      5 => 3,
      6 => 3,
      7 => 4,
      8 => 4,
      9 => 4,
      10 => 4,
      11 => 5,
      12 => 5,
      13 => 5,
      14 => 5,
      15 => 5,
      16 => 6,
      17 => 6,
      18 => 6,
      19 => 6,
      20 => 6,
      21 => 6,
      22 => 7,
      23 => 7,
      24 => 7,
      25 => 7,
      26 => 7,
      27 => 7,
      28 => 7,
      29 => 8,
    ];
    $necessaryEnergy = Globals::getRound() * 6;

    $cEnergies = Companies::getAll()
      ->map(function ($c) {
        return $c->getEnergy();
      })
      ->toAssoc();
    arsort($cEnergies);

    $first = 0;
    $countFirst = 0;
    $countSecond = 0;
    $second = 0;
    $gains = [];
    $turnOrder = [];

    foreach ($cEnergies as $cId => $energy) {
      if (!isset($gains[$cId])) {
        $gains[$cId] = [];
      }
      $turnOrder[$energy][] = $cId;

      // Score VP based on energy track
      // get position on the board
      if ($energy != 0 && $energy >= $first) {
        $first = $energy;
        $countFirst++;
        $gains[$cId]['track'] = 1;
      } elseif ($energy != 0 && $energy >= $second) {
        $second = $energy;
        $countSecond++ . ($gains[$cId]['track'] = 2);
      }

      // Score credit based on energy track
      $gains[$cId]['position'] = [CREDIT => $creditMap[$energy] ?? 8];
      $gains[$cId]['malus'] = [VP => $energy == 0 ? -3 : 0];

      // score for bonus
      if ($energy >= 6) {
        $bonus = $this->calculateRoundBonus($cId);
        if ($energy >= $necessaryEnergy) {
          $malus = 0;
        } else {
          $malus = ceil(($necessaryEnergy - $energy) / 6) * 4;
        }
        if ($bonus - $malus > 0) {
          $gains[$cId]['bonus'] = [VP => $bonus - $malus];
        }
      }
    }

    $flow = ['type' => NODE_SEQ, 'childs' => []];
    foreach ($gains as $cId => $bonuses) {
      $node = ['action' => GAIN, 'automatic' => true, 'cId' => $cId, 'args' => ['cId' => $cId]];
      foreach ($bonuses as $bonusType => $resources) {
        if ($bonusType == 'track') {
          if (!isset($node['args'][VP])) {
            $node['args'][VP] = 0;
          }

          if ($resources == 1 && $countFirst == 1) {
            $node['args'][VP] += 6;
          } elseif ($resources == 1) {
            // we split evenly
            $node['args'][VP] += ceil(8 / $countFirst);
          }
          if ($resources == 2 && $countFirst == 1 && $countSecond == 1) {
            $node['args'][VP] += 2;
          } elseif ($resources == 2 && $countFirst == 1 && $countSecond != 1) {
            $node['args'][VP] += 1;
          }
        } else {
          $node['args'] = array_merge($node['args'], $resources);
        }
      }
      $flow['childs'][] = $node;
    }
    Engine::setup($flow, ['method' => 'stPreEndOfTurn']);

    // Change turn order
    ksort($turnOrder, SORT_NUMERIC);
    $finalOrder = [];
    $cCount = 1;
    foreach ($turnOrder as $en => &$companies) {
      // No tie in energy production
      if (count($companies) == 1) {
        $cId = array_pop($companies);
        $finalOrder[$cCount] = $cId;
        Companies::get($cId)->setNo($cCount);
        $cCount++;
      } else {
        usort($companies, function ($c1, $c2) {
          return Companies::get($c2)->getNo() - Companies::get($c1)->getNo();
        });

        for ($i = 0; $i < count($companies) / 2; $i++) {
          $c1 = $companies[$i];
          $c2 = $companies[count($companies) - 1 - $i];
          // setting turn order for player that was placed before
          $finalOrder[$cCount] = $c2;
          Companies::get($c2)->setNo($cCount);
          $cCount++;

          $finalOrder[$cCount] = $c1;
          Companies::get($c1)->setNo($cCount);
          $cCount++;
        }
      }
    }
    // return home of engineers
    Companies::returnHome();

    // reset energy on track
    foreach (Companies::getAll() as $cId => $company) {
      $company->setEnergy(0);
    }
    Meeples::move(
      Meeples::getFilteredQuery(null, null, [SCORE])
        ->get()
        ->getIds(),
      'energy-track-0'
    );
    Notifications::moveTokens(Meeples::getFilteredQuery(null, null, [SCORE])->get());

    // TODO: remove advanced tiles
    Engine::proceed();
  }

  function stPreEndOfTurn()
  {
    // Next turn or final scoring
    $round = Globals::getRound();
    if ($round < 5) {
      $this->gamestate->jumpToState(ST_BEFORE_START_OF_ROUND);
    } else {
      $this->gamestate->nextState('endScoring');
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
