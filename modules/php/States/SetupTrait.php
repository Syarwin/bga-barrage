<?php
namespace BRG\States;
use BRG\Core\Globals;
use BRG\Core\Notifications;
use BRG\Core\Preferences;
use BRG\Core\Stats;
use BRG\Helpers\Utils;
use BRG\Managers\Companies;
use BRG\Managers\Players;
use BRG\Managers\Officers;
use BRG\Managers\Meeples;
use BRG\Managers\Contracts;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\AutomaCards;
use BRG\Map;

trait SetupTrait
{
  /*
   * setupNewGame:
   */
  protected function setupNewGame($players, $options = [])
  {
    Globals::setupNewGame($players, $options);
    Players::setupNewGame($players, $options);
    Preferences::setupNewGame($players, $options);
    AutomaCards::setupNewGame($options);
    Map::init();
    Stats::checkExistence();

    if (Globals::getSetup() == \BRG\OPTION_SETUP_SEED) {
      die('TODO : seed mode');
      return;
    }

    // 6] Draw random headstream tiles
    $headstreams = Map::getHeadstreams();
    $tiles = Utils::rand([HT_1, HT_2, HT_3, HT_4, HT_5, HT_6, HT_7, HT_8], count($headstreams));
    $t = [];
    foreach ($headstreams as $i => $hId) {
      $t[$hId] = $tiles[$i];
    }
    Globals::setHeadstreams($t);

    // 7] Place 5 bonus tiles
    $bonusTiles = [BONUS_CONTRACT, \BONUS_BASE, \BONUS_ELEVATION, \BONUS_CONDUIT, \BONUS_POWERHOUSE];
    if (!Globals::isBeginner()) {
      $bonusTiles[] = \BONUS_ADVANCED_TILE;
    }
    if (Globals::isLWP()) {
      $bonusTiles[] = \BONUS_EXTERNAL_WORK;
      $bonusTiles[] = \BONUS_BUILDING;
    }
    $tiles = Utils::rand($bonusTiles, 5);
    Globals::setBonusTiles($tiles);
    foreach ($tiles as $i => $tile) {
      $statName = 'setRound' . ($i + 1) . 'Obj';
      Stats::$statName($tile);
    }

    // 8] Draw 1 hidden objective tile
    $objTile = Utils::rand(OBJECTIVE_TILES)[0];
    Globals::setObjectiveTile($objTile);
    Stats::setFinalObj($objTile);

    // 9] 10] Draw contracts
    Contracts::setupNewGame();

    // 11] Place neutral dams
    Map::placeNeutralDams();

    // Introductory setup : assign companies
    if (Globals::isBeginner()) {
      $i = 1;
      foreach (Players::getAll() as $pId => $player) {
        $matchup = INTRODUCTORY_MATCHUPS[Companies::count() - $i++];
        $company = Companies::assignCompany($player, $matchup[0], $matchup[1]);
        $contract = Contracts::get($matchup[2]);
        $contract->pick($company);
      }
      $this->reloadPlayersBasicInfos();

      // Handle automa
      $nAutoma = 0;
      for (; $i <= Companies::count(); $i++) {
        $matchup = INTRODUCTORY_MATCHUPS[Companies::count() - $i++];
        $fakePId = ($nAutoma + 1) * -5 + $options[\BRG\OPTION_LVL_AUTOMA_1 + $nAutoma];
        $company = Companies::assignCompanyAutoma($fakePId, $matchup[0], $matchup[1]);
        // NO CONTRACT FOR AUTOMA
        $nAutoma++;
      }

      $this->setupCompanies(true);
    }

    $this->activeNextPlayer();
  }

  public function stSetupBranch()
  {
    if (Globals::isBeginner()) {
      $this->gamestate->nextState('start');
    } else {
      // 12] Draw advanced tech tiles
      TechnologyTiles::setupAdvancedTiles();

      if (Globals::getSetup() == \BRG\OPTION_SETUP_FREE) {
        die('TODO: free setup mode');
        return;
      } else {
        $n = Companies::count();

        // 13] Draw random setup and go to draft
        $companies = Companies::randomStartingPick($n);
        // Always drawing 4 officers (in case of Mahiri)
        $officers = Officers::randomStartingPick(4);

        $matchups = [];
        foreach ($companies as $i => $cId) {
          $matchups[$i] = [
            'cId' => $cId,
            'xId' => $officers[$i],
          ];
        }

        $isMahiri = in_array(XO_MAHIRI, array_slice($officers, 0, $n));
        Globals::setMahiriAddXO($isMahiri ? array_diff($officers, [\XO_MAHIRI]) : []);

        Globals::setStartingMatchups($matchups);
        Contracts::randomStartingPick($n);
        $this->changePhase('pickStart');
        $this->gamestate->nextState('pick');
      }
    }
  }

  function setupCompanies($initMeeples = false)
  {
    $companies = Companies::getAll();
    if ($initMeeples) {
      $meeples = Meeples::setupCompanies($companies);
      $tiles = TechnologyTiles::setupCompanies($companies);
    }

    // Create turn order
    $turnOrder = [];
    foreach ($companies as $cId => $company) {
      $turnOrder[$company->getNo() - 1] = $cId;
    }
    Notifications::updateTurnOrder(array_values($turnOrder));
    Companies::changeActive($turnOrder[0]);
    Globals::setTurnOrder($turnOrder);
  }

  ///////////////////////////////////////////////////
  //  ____  _      _      ____  _             _
  // |  _ \(_) ___| | __ / ___|| |_ __ _ _ __| |_
  // | |_) | |/ __| |/ / \___ \| __/ _` | '__| __|
  // |  __/| | (__|   <   ___) | || (_| | |  | |_
  // |_|   |_|\___|_|\_\ |____/ \__\__,_|_|   \__|
  //
  ///////////////////////////////////////////////////

  function stPickStartNext()
  {
    $pId = $this->activeNextPlayer();
    $args = $this->argsPickStart();
    if (empty(Globals::getStartingMatchups())) {
      $this->setupCompanies();
      $this->gamestate->nextState('done');
    } elseif (!empty(Companies::getCorrespondingIds([$pId]))) {
      // Handle automa
      $nAutoma = 0;
      $matchups = array_values($args['matchups']);
      for ($i = Players::count(); $i < Companies::count(); $i++) {
        $matchup = $matchups[$nAutoma];
        $lvl = $this->getGameOptionValue(\BRG\OPTION_LVL_AUTOMA_1 + $nAutoma);
        $fakePId = ($nAutoma + 1) * -5 + $lvl;
        $company = Companies::assignCompanyAutoma($fakePId, $matchup['cId'], $matchup['xId']);
        // NO CONTRACT FOR AUTOMA
        $meeples = Meeples::setupCompany($company);
        $tiles = TechnologyTiles::setupCompany($company);
        Notifications::assignCompanyAutoma($company, $meeples, $tiles);
        $nAutoma++;
      }

      // Remove remaining contracts
      $contractIds = Contracts::clearMatchups();
      Notifications::clearMatchups($contractIds);

      // Start the game
      $this->setupCompanies();
      $this->gamestate->nextState('done');
    } else {
      // Only one left ? => Autopick
      if (count($args['matchups']) == 1) {
        $this->actPickStart(array_keys($args['matchups'])[0], $args['contracts']->first()->getId(), true);
      } else {
        $this->gamestate->nextState('pick');
      }
    }
  }

  function argsPickStart()
  {
    // Fetching additional matchups datas
    $matchups = Globals::getStartingMatchups();
    foreach ($matchups as &$matchup) {
      $company = Companies::getInstance($matchup['cId']);
      $matchup['company'] = $company;

      $officer = Officers::getInstance($matchup['xId']);
      $matchup['officer'] = $officer;
    }

    // Fetching contracts
    $contracts = Contracts::getStartingPick();
    return [
      'matchups' => $matchups,
      'contracts' => $contracts,
    ];
  }

  function actPickStart($matchupId, $contractId, $auto = false)
  {
    if (!$auto) {
      self::checkAction('actPickStart');
    }
    $args = $this->argsPickStart();
    $matchup = $args['matchups'][$matchupId] ?? null;
    if (is_null($matchup)) {
      throw new \BgaVisibleSystemException('Invalid matchup id');
    }
    $contract = $args['contracts'][$contractId] ?? null;
    if (is_null($contract)) {
      throw new \BgaVisibleSystemException('Invalid contract id');
    }

    $player = Players::getActive();
    $company = Companies::assignCompany($player, $matchup['cId'], $matchup['xId']);
    $this->reloadPlayersBasicInfos();

    $meeples = Meeples::setupCompany($company);
    $tiles = TechnologyTiles::setupCompany($company);

    Notifications::assignCompany($player, $company, $meeples, $tiles);

    $contract->pick($company);
    Notifications::pickContracts($company, [$contract]);

    $matchups = Globals::getStartingMatchups();
    unset($matchups[$matchupId]);
    Globals::setStartingMatchups($matchups);

    $this->gamestate->nextState('nextPick');
  }
}
