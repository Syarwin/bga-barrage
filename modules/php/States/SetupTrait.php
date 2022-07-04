<?php
namespace BRG\States;
use BRG\Core\Globals;
use BRG\Core\Notifications;
use BRG\Core\Preferences;
use BRG\Helpers\Utils;
use BRG\Managers\Companies;
use BRG\Managers\Players;
use BRG\Managers\Officers;
use BRG\Managers\Meeples;
use BRG\Managers\Contracts;
use BRG\Managers\TechnologyTiles;
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
    Map::init();

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

    // 8] Draw 1 hidden objective tile
    $objTile = Utils::rand(OBJECTIVE_TILES)[0];
    Globals::setObjectiveTile($objTile);

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
        $n = Players::count();

        // 13] Draw random setup and go to draft
        $companies = Companies::randomStartingPick($n);
        if ($n == 2) {
          // for Mahiri
          $officers = Officers::randomStartingPick($n + 2);
        } else {
          $officers = Officers::randomStartingPick($n);
        }
        $matchups = [];
        foreach ($companies as $i => $cId) {
          $matchups[$i] = [
            'cId' => $cId,
            'xId' => $officers[$i],
          ];
        }
        if ($n == 2) {
          Globals::setMahiriAddXO([$officers[2], $officers[3]]);
        }
        Globals::setStartingMatchups($matchups);
        Contracts::randomStartingPick($n);
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
    if (empty(Globals::getStartingMatchups())) {
      // TODO : assign random companies to automas
      $this->setupCompanies();
      $this->gamestate->nextState('done');
    } else {
      $this->gamestate->nextState('pick');
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
    $args = $this->getArgs();
    $matchup = $args['matchups'][$matchupId] ?? null;
    if (is_null($matchup)) {
      throw new \BgaVisibleSystemException('Invalid matchup id');
    }
    $contract = $args['contracts'][$contractId] ?? null;
    if (is_null($contract)) {
      throw new \BgaVisibleSystemException('Invalid contract id');
    }

    $player = Players::getCurrent();
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
