<?php
namespace BRG\States;
use BRG\Core\Globals;
use BRG\Core\Notifications;
use BRG\Core\Engine;
use BRG\Managers\Players;
use BRG\Managers\Companies;
use BRG\Managers\Meeples;
use BRG\Managers\Fences;
use BRG\Managers\Actions;
use BRG\Managers\ActionSpaces;
use BRG\Managers\AutomaCards;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\ExternalWorks;
use BRG\Managers\Contracts;
use BRG\Models\PlayerBoard;
use BRG\Helpers\Utils;
use BRG\Actions\Construct;
use BRG\Actions\Gain;
use BRG\Actions\PlaceDroplet;
use BRG\Actions\Produce;
use BRG\Actions\FulfillContract;
use BRG\Actions\PlaceStructure;
use BRG\Actions\ExternalWork;
use BRG\Map;

trait AutomaTurnTrait
{
  function stPreAutomaTurn()
  {
    AutomaCards::flip();
    $this->gamestate->nextState();
  }

  function actRunAutoma()
  {
    $company = Companies::getActive();
    if (!$company->isAI()) {
      return;
    }

    $actions = $this->computeAutomaTurn();
    if (!empty($actions)) {
      $this->automaTakeActions($actions);
      $this->nextPlayerCustomOrder('actionPhase');
    } else {
      $this->actSkip(true);
    }
  }

  function argsAutomaTurn()
  {
    return [
      'actions' => self::computeAutomaTurn(),
      'cId' => Companies::getActiveId(),
    ];
  }

  function getAutomaFlow()
  {
    return AutomaCards::getUiData()['front']->getFlow();
  }

  function getAutomaCriteria()
  {
    return AutomaCards::getUiData()['back']->getCriteria();
  }

  /**
   * computeAutomaTurn(): given the automa cards, compute the list of actions the automa will take
   */
  function computeAutomaTurn()
  {
    $company = Companies::getActive();
    return $this->convertFlowToAutomaActions($this->getAutomaFlow());
  }

  /**
   * Given a flow following automa card syntax, compute the possible corresponding actions along with results
   */
  function convertFlowToAutomaActions($flow)
  {
    $company = Companies::getActive();
    $nEngineers = $company->countAvailableEngineers();
    $actions = [];
    foreach ($flow as $action) {
      $requiredEngineers = $action['nEngineers'] ?? 0;
      if ($requiredEngineers > 0 && $nEngineers <= 0) {
        continue;
      }

      $res = $this->canAutomaTakeAction($action);
      if ($res !== false) {
        $actions[] = [
          'action' => $action,
          'result' => $res,
        ];
        $nEngineers -= $requiredEngineers;

        // For these actions, the automa turn ends right away (except if it's a contract reward)
        if (
          in_array($action['type'], [PRODUCE, CONSTRUCT, ROTATE_WHEEL, GAIN_MACHINE, PATENT, EXTERNAL_WORK]) &&
          $requiredEngineers > 0
        ) {
          break;
        }
      }
    }

    return $actions;
  }

  /**
   * canAutomaTakeAction($action) : given an action with args, return
   *   - either false if the automa cannot take the action
   *   - or relevant informations to know what this action will result in
   */
  function canAutomaTakeAction($action)
  {
    $company = Companies::getActive();
    $type = $action['type'];

    $condition = $action['condition'] ?? null;
    if ($condition == NOT_LAST_ROUND && Globals::getRound() == 5) {
      return false;
    }

    ///////////////////////////////////////////
    // ENERGY: foo action to let automa gain energy
    if ($type == ENERGY) {
      return true;
    }
    ///////////////////////////////////////////
    // Produce : must be able to produce + fulfill a contract + has a reason to gain energy on the track (see below)
    elseif ($type == PRODUCE) {
      return $this->canAutomaTakeProduceAction($company, $action);
    }
    ///////////////////////////////////////////
    // Place Droplet : only if it can reach automa's barrage
    elseif ($type == \PLACE_DROPLET) {
      $placedDroplets = $this->canAutomaTakePlaceDropletAction($company, $action);
      return empty($placedDroplets) ? false : ['locations' => $placedDroplets];
    }
    ///////////////////////////////////////////
    // Construct : only if it has available machinery and tech tile (see below)
    elseif ($type == CONSTRUCT) {
      return $this->canAutomaTakeConstructAction($company, $action);
    }
    ///////////////////////////////////////////
    // Place Structure : only if an available spot exist
    elseif ($type == \PLACE_STRUCTURE) {
      return $this->canAutomaTakePlaceStructureAction($company, $action);
    }
    //////////////////////////////////////////
    // External Work : LWP
    elseif ($type == \EXTERNAL_WORK) {
      if (!Globals::isLWP()) {
        return false;
      }

      foreach ($action['order'] as $i) {
        if ($company->canTakeAction(EXTERNAL_WORK, ['position' => $i], false)) {
          return ['position' => $i];
        }
      }
      return false;
    }
    //////////////////////////////////////////
    // Rotate wheel : wheel must be non-empty
    elseif ($type == \ROTATE_WHEEL) {
      return !Meeples::getOnWheel($company->getId())->empty() ||
        !TechnologyTiles::getOnWheel($company->getId())->empty();
    }
    //////////////////////////////////////////
    // Gain machine : automa will not take this action in last round of the game
    elseif ($type == \GAIN_MACHINE) {
      // Specific machines => no choices
      $machines = $action['machines'] ?? [\ANY_MACHINE => 1];
      if (!isset($machines[ANY_MACHINE])) {
        return $machines; // No choice ? we are done
      }
      $res = [
        EXCAVATOR => $company->countReserveResource(EXCAVATOR) + ($machines[EXCAVATOR] ?? 0),
        MIXER => $company->countReserveResource(MIXER) + ($machines[MIXER] ?? 0),
      ];
      for ($i = 1; $i <= $machines[ANY_MACHINE]; $i++) {
        $type = $res[EXCAVATOR] <= $res[MIXER] ? EXCAVATOR : MIXER;
        $res[$type]++;
        $machines[$type] = ($machines[$type] ?? 0) + 1;
      }
      unset($machines[ANY_MACHINE]);
      return $machines;
    }
    ////////////////////////////////////////
    // Gain VP : always possible as last resort
    elseif ($type == \GAIN_VP) {
      return true;
    }
    ////////////////////////////////////////
    // Discard contracts : always possible
    elseif ($type == \TAKE_CONTRACT) {
      return true;
    }
    ////////////////////////////////////////
    // Patent for advanced tech tile : possible if a tile of this type is available
    elseif ($type == PATENT) {
      if (Globals::isBeginner() || ($action['structure'] == BUILDING && !Globals::isLWP())) {
        return false;
      }

      $maxLvl = -1;
      $maxTile = null;
      $maxPosition = null;
      for ($position = 1; $position <= 3; $position++) {
        $tile = TechnologyTiles::getFilteredQuery(null, 'patent_' . $position)
          ->get()
          ->first();
        if (!is_null($tile) && $tile->canConstruct($action['structure']) && $tile->getLvl() > $maxLvl) {
          $maxLvl = $tile->getLvl();
          $maxTile = $tile->getId();
          $maxPosition = $position;
        }
      }

      return is_null($maxTile)
        ? false
        : [
          'actionSpaceId' => 'patent-p' . $maxPosition,
          'position' => $maxPosition,
          'tileId' => $maxTile,
        ];
    }
  }

  /**
   * automaTakeAction($action, $result)
   */
  function automaTakeActions($actions)
  {
    foreach ($actions as $action) {
      $this->automaTakeAction($action['action'], $action['result']);
    }
  }

  function automaTakeAction($action, $result)
  {
    $company = Companies::getActive();
    $type = $action['type'];

    // Place engineers
    $nEngineers = $action['nEngineers'] ?? 0; // 0 is useful for contract rewards
    if ($nEngineers > 0) {
      $actionSpaceId = $result['actionSpaceId'] ?? null;
      $boards = [
        PRODUCE => BOARD_TURBINE,
        PLACE_DROPLET => BOARD_WATER,
        CONSTRUCT => BOARD_COMPANY,
        EXTERNAL_WORK => BOARD_EXTERNAL_WORK,
        ROTATE_WHEEL => BOARD_WORSKHOP,
        GAIN_MACHINE => BOARD_MACHINERY_SHOP,
        GAIN_VP => BOARD_BANK,
        TAKE_CONTRACT => BOARD_CONTRACT,
        PATENT => \BOARD_PATENT,
      ];
      $board = ActionSpaces::getBoard($boards[$type]);

      if (is_null($actionSpaceId)) {
        $spaces = $board::getOrderedPlayableSpaces($company);
        foreach ($spaces as $space) {
          if (Meeples::getOnSpace($space['uid'])->empty()) {
            $actionSpaceId = $space['uid'];
            break;
          }
        }
        // No free space => just put it on the first space of that board
        if (is_null($actionSpaceId)) {
          $actionSpaceId = $spaces[0]['uid'];
        }
      }

      // Get max state
      $state = Meeples::getExtremePosition(true, $actionSpaceId);
      // Put engineer on top of that
      $nEngineers = min($nEngineers, $company->countAvailableEngineers());
      $engineers = $company->placeEngineer($actionSpaceId, $nEngineers, $state + ($state > 0 ? 1 : 0));
      Notifications::placeEngineers($company, $engineers, $board);
    }

    // Take negative vp for the action
    $vp = $action['vp'] ?? 0;
    if ($vp < 0) {
      $company->incScore($vp, clienttranslate('for taking this action'));
    }

    ///////////////////////////////////////////
    // Produce
    if ($type == PRODUCE) {
      $system = $result['system'];
      Produce::produce($system, $system['droplets']);
      $contract = $result['contract'];
      FulfillContract::fulfillContract($contract);
    }
    ///////////////////////////////////////////
    // Place Droplet
    elseif ($type == \PLACE_DROPLET) {
      PlaceDroplet::placeDroplets($company, $result['locations'], $actionSpaceId ?? null, $action['flow']);
    }
    ///////////////////////////////////////////
    // Construct
    elseif ($type == CONSTRUCT) {
      Engine::runAutoma($result['flow']);
    }
    ///////////////////////////////////////////
    // Place Structure
    elseif ($type == PLACE_STRUCTURE) {
      PlaceStructure::placeStructure($result['spaceId'], $action['structure']);
    }
    //////////////////////////////////////////
    // External Work : LWP
    elseif ($type == \EXTERNAL_WORK) {
      $work = ExternalWorks::getFilteredQuery(null, 'work_' . $result['position'])
        ->get()
        ->first();

      ExternalWork::fulfillExternalWork($work);
    }
    //////////////////////////////////////////
    // Rotate wheel
    elseif ($type == \ROTATE_WHEEL) {
      for ($i = 0; $i < $action['n']; $i++) {
        $company->rotateWheel();
      }
    }
    //////////////////////////////////////////
    // Gain machine
    elseif ($type == \GAIN_MACHINE) {
      Gain::gainResources($company, $result);
    }
    ////////////////////////////////////////
    // Gain VP
    elseif ($type == \GAIN_VP) {
      $company->incScore($action['vp']);
    }
    ////////////////////////////////////////
    // Discard contracts
    elseif ($type == \TAKE_CONTRACT) {
      $ids = Contracts::emptyStack($action['contract']);
      Notifications::emptyContractStack($company, $action['contract'], $ids);
    }
    ////////////////////////////////////////
    // Patent for advanced tech tile
    elseif ($type == PATENT) {
      Engine::runAutoma([
        'action' => PATENT,
        'args' => ['position' => $result['position']],
      ]);
    }

    // Gain energy
    $energy = $action['energy'] ?? 0;
    if ($energy > 0) {
      $company->incEnergy($energy);
    }
  }

  /////////////////////////////////////////////
  //  ____                _
  // |  _ \ _ __ ___   __| |_   _  ___ ___
  // | |_) | '__/ _ \ / _` | | | |/ __/ _ \
  // |  __/| | | (_) | (_| | |_| | (_|  __/
  // |_|   |_|  \___/ \__,_|\__,_|\___\___|
  //
  /////////////////////////////////////////////

  public function canAutomaTakeProduceAction($company, $action)
  {
    // Can we produce energy ?
    $systems = Map::getProductionSystems($company, $action['bonus'] ?? 0, null, false, false);
    if (empty($systems)) {
      return false;
    }
    // Compute the max amount of energy producable
    $maxProd = 0;
    $maxSystem = null;
    foreach ($systems as $system) {
      $prod = $system['productions'][$system['droplets']];
      if ($prod > $maxProd) {
        $maxProd = $prod;
        $maxSystem = $system;
      }
    }
    // TODO : tie breaker

    // Check energy track requirement
    $energy = $company->getEnergy();
    $round = Globals::getRound();
    $necessaryEnergy = $round * 6;
    if ($energy >= $necessaryEnergy) {
      // No need to produce unless we are not first
      $maxEnergy = 0;
      foreach (Companies::getAll() as $cId => $comp) {
        if ($cId != $company->getId()) {
          $maxEnergy = max($maxEnergy, $comp->getEnergy());
        }
      }
      if ($maxEnergy < $energy) {
        return false;
      }
    }

    // Can we fulfill at least one contract with that much energy?
    $contractFound = false;
    $maxContract = null;
    foreach (Contracts::getAvailableToTake() as $contract) {
      if ($contract->getCost() <= $maxProd) {
        if ($contract->getType() == $action['contract']) {
          $contractFound = true;
        }
        if ($maxContract == null || $maxContract->getCost() <= $contract->getCost()) {
          $maxContract = $contract;
        }
      }
    }
    if (!$contractFound) {
      return false;
    }

    return [
      'system' => $maxSystem,
      'contract' => $maxContract, // TODO : getId ??
    ];
  }

  /////////////////////////////////////////////////////
  //   ____                _                   _
  //  / ___|___  _ __  ___| |_ _ __ _   _  ___| |_
  // | |   / _ \| '_ \/ __| __| '__| | | |/ __| __|
  // | |__| (_) | | | \__ \ |_| |  | |_| | (__| |_
  //  \____\___/|_| |_|___/\__|_|   \__,_|\___|\__|
  /////////////////////////////////////////////////////
  public function canAutomaTakeConstructAction($company, $action)
  {
    $structure = $action['structure'];

    // Find all the possible constructable spots
    $pairs = Construct::getConstructablePairs($company, false, false, [
      'type' => $structure,
      'constraints' => $action['constraints'] ?? null,
      'n' => $action['n'] ?? null,
    ]);
    if (empty($pairs)) {
      return false;
    }

    // Now let's find the space from these pairs
    $spaceIds = array_unique(
      array_map(function ($pair) {
        return $pair['spaceId'];
      }, $pairs)
    );
    if ($structure == BUILDING) {
      $buildings = [];
      foreach ($spaceIds as $sId) {
        $t = explode('-', $sId);
        $buildings[$t[1]][] = $t[2];
      }

      $keys = array_keys($buildings);
      $key = $action['constraints'] == 'down' ? $keys[0] : $keys[count($keys) - 1];
      $n = $buildings[$key][0];
      $spaceId = "buildingslot-$key-$n";
    } else {
      $spaceId = $this->getAutomaStructureEmplacement($company, $structure, $spaceIds);
    }

    // Now find the good tile for that spot
    $maxLvl = -1;
    $maxPair = null;
    foreach ($pairs as $pair) {
      if ($pair['spaceId'] != $spaceId || $pair['tileLvl'] < $maxLvl) {
        continue;
      }
      if ($pair['tileLvl'] > $maxLvl || $pair['tileStructureType'] == $structure) {
        $maxLvl = $pair['tileLvl'];
        $maxPair = $pair;
      }
    }

    if (is_null($maxPair)) {
      var_dump($spaceId, $pairs);
      die("Automa error : can't find corresponding tile");
    }

    return $maxPair;
  }

  public function canAutomaTakePlaceStructureAction($company, $action)
  {
    $structure = $action['structure'];

    // Find all the possible constructable spots
    $spaces = PlaceStructure::getAvailableSpaces($company, false, [
      'type' => $structure,
      'constraints' => $action['constraints'] ?? null,
      'n' => $action['n'] ?? null,
    ]);
    if (empty($spaces)) {
      return false;
    }

    // Now let's find the space from these pairs
    $spaceIds = array_keys($spaces);
    $spaceId = $this->getAutomaStructureEmplacement($company, $structure, $spaceIds);

    return ['spaceId' => $spaceId];
  }

  //////////////////////////////////////////////////////////////////
  //  ____  _                  ____                  _      _
  // |  _ \| | __ _  ___ ___  |  _ \ _ __ ___  _ __ | | ___| |_
  // | |_) | |/ _` |/ __/ _ \ | | | | '__/ _ \| '_ \| |/ _ \ __|
  // |  __/| | (_| | (_|  __/ | |_| | | | (_) | |_) | |  __/ |_
  // |_|   |_|\__,_|\___\___| |____/|_|  \___/| .__/|_|\___|\__|
  //                                          |_|
  //////////////////////////////////////////////////////////////////

  public function canAutomaTakePlaceDropletAction($company, $action)
  {
    $flowing = $action['flow'] ?? false;
    // Get the basins space ids with automa's barrage
    $basins = array_keys(Map::getBasins());
    Utils::filter($basins, function ($basinId) use ($company) {
      return !is_null(Map::getBuiltStructure($basinId, $company));
    });

    // Current status
    list($currentStatus, $p) = Map::emulateFlowDroplets([], $flowing);
    $nTotal = totalDroplets($basins, $currentStatus);
    $placedDroplets = [];
    $remaining = 0;
    $order = $this->getAutomaCriteria()[PLACE_DROPLET];
    for ($i = 1; $i <= $action['n']; $i++) {
      $added = false;
      foreach ($order as $hs) {
        $headstream = 'H' . $hs;
        $droplets = $placedDroplets;
        for ($j = 0; $j < 1 + $remaining; $j++) {
          $droplets[] = $headstream;
        }

        list($status, $p) = Map::emulateFlowDroplets($droplets, $flowing);
        $n = totalDroplets($basins, $status);
        if ($n > $nTotal) {
          $added = true;
          $nTotal = $n;
          $placedDroplets = $droplets;
          $remaining = 0;
          break;
        }
      }

      if (!$added) {
        $remaining++;
      }
    }
    return $placedDroplets;
  }
}

// Function to count total number of droplets given a droplet status
function totalDroplets($arrBasins, $status)
{
  $n = 0;
  foreach ($arrBasins as $bId) {
    $n += $status[$bId] ?? 0;
  }
  return $n;
}
