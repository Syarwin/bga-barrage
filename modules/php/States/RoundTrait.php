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

    // water flow
    $droplets = Meeples::getSelectQuery()
      ->where('type', DROPLET)
      ->get();
    $notifs = [];
    foreach ($droplets as $dId => $droplet) {
      $notifs = array_merge($notifs, Map::flow($droplet));
    }
    Notifications::moveDroplets($notifs);

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
          foreach ($resources as $type => $amount) {
            if (isset($node['args'][$type])) {
              $node['args'][$type] += $amount;
            } else {
              $node['args'][$type] = $amount;
            }
          }
        }
      }
      $flow['childs'][] = $node;
    }
    Engine::setup($flow, ['method' => 'stPreEndOfTurn']);

    if (Globals::getRound() < 5) {
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

      // TODO: Notify turn order
      Notifications::updateTurnOrder(Companies::getAll());

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

      // remove the bonus tile
      Notifications::removeBonusTile(Globals::getRound());
    }

    Engine::proceed();
  }

  function stPreEndOfTurn()
  {
    // Next turn or final scoring
    $round = Globals::getRound();
    if ($round < 5) {
      $this->gamestate->jumpToState(ST_BEFORE_START_OF_ROUND);
    } else {
      $this->gamestate->jumpToState(ST_PRE_END_OF_GAME);
    }
  }

  function stEndScoring()
  {
    $flow = ['type' => NODE_SEQ, 'childs' => []];
    $flow['childs'] = array_merge($this->calculateObjectiveTile(), $flow['childs']);
    foreach (Companies::getAll() as $cId => $company) {
      $count = 0;
      foreach ([CREDIT, EXCAVATOR, MIXER, EXCAMIXER] as $type) {
        $count += $company->countReserveResource($type);
      }
      if ($count >= 5) {
        $flow['childs'][] = [
          'action' => GAIN,
          'source' => clienttranslate('bundle of 5 resources'),
          'args' => [
            'cId' => $company->getId(),
            VP => intdiv($count, 5),
          ],
        ];
      }
      // count number of water on barrage
      $drops = 0;
      foreach (Meeples::getFilteredQuery($cId, null, [BASE]) as $mId => $m) {
        if ($m['location'] == 'company') {
          continue;
        }
        $drops += count(Meeples::getOnSpace($m['location'], DROPLET, $cId));
      }
      if ($drops != 0) {
        $flow['childs'][] = [
          'action' => GAIN,
          'source' => clienttranslate('droplets retained by dams'),
          'args' => [
            'cId' => $company->getId(),
            VP => $drops,
          ],
        ];
      }
    }
    // debug
    // Engine::setup($flow, ['state' => ST_BEFORE_START_OF_ROUND]);

    Engine::setup($flow, ['state' => ST_END_GAME]);
    Engine::proceed();
  }
}
