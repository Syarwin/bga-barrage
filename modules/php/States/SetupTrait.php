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
    //    Contracts::setupNewGame();

    // 11] Place neutral dams
    Map::placeNeutralDams();

    $this->activeNextPlayer();
  }

  public function stSetupBranch()
  {
    if (Globals::isBeginner()) {
      // TODO : default setup
      die('TODO: Introductory matchups');
    } else {
      // 12] Draw advanced tech tiles
      // TODO TechnologyTiles::setupAdvancedTiles();

      if (Globals::getSetup() == \BRG\OPTION_SETUP_FREE) {
        die('TODO: free setup mode');
        return;
      } else {
        $n = Players::count();

        // 13] Draw random setup and go to draft
        $companies = Companies::randomStartingPick($n);
        $officers = Officers::randomStartingPick($n);
        $matchups = [];
        foreach ($companies as $i => $cId) {
          $matchups[$i] = [
            'cId' => $cId,
            'xId' => $officers[$i],
          ];
        }
        Globals::setStartingMatchups($matchups);
        Contracts::randomStartingPick($n);
        $this->gamestate->nextState('pick');
      }
    }
  }

  function setupCompanies($silent = false)
  {
    $companies = Companies::getAll();
    $meeples = Meeples::setupCompanies($companies);
    $tiles = TechnologyTiles::setupCompanies($companies);
    if (!$silent) {
      Notifications::setupCompanies($meeples, $tiles);
    }

    // Create turn order
    $turnOrder = [];
    foreach ($companies as $cId => $company) {
      $turnOrder[$company->getNo() - 1] = $cId;
    }
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
    $matchups = Globals::getStartingMatchups();
    foreach ($matchups as &$matchup) {
      $company = Companies::getInstance($matchup['cId']);
      $matchup['cName'] = $company->getCname();

      $officer = Officers::getInstance($matchup['xId']);
      $matchup['xName'] = $officer->getName();
    }
    return [
      'matchups' => $matchups,
    ];
  }

  function actPickStart($matchupId, $contract, $auto = false)
  {
    if (!$auto) {
      self::checkAction('actPickStart');
    }
    $args = $this->getArgs();
    $matchup = $args['matchups'][$matchupId] ?? null;
    if (is_null($matchup)) {
      throw new \BgaVisibleSystemException('Invalid matchup id');
    }
    // TODO : contracts

    $player = Players::getCurrent();
    $company = Companies::assignCompany($player, $matchup['cId'], $matchup['xId']);
    $this->reloadPlayersBasicInfos();
    Notifications::assignCompany($player, $company);

    // TODO : contracts

    $matchups = Globals::getStartingMatchups();
    unset($matchups[$matchupId]);
    Globals::setStartingMatchups($matchups);

    $this->gamestate->nextState('nextPick');
  }
}
