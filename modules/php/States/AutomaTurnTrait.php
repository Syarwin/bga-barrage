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
use BRG\Models\PlayerBoard;
use BRG\Helpers\Utils;
use BRG\Actions\Construct;
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
    $actions = $this->computeAutomaTurn();
    foreach ($actions as $action) {
      $this->automaTakeAction($action['action'], $action['result']);
    }

    $this->nextPlayerCustomOrder('actionPhase');
  }

  function getAutomaFlow()
  {
    return AutomaCards::getUiData()['front']->getFlow();
  }

  function getAutomaCriteria()
  {
    return AutomaCards::getUiData()['back']->getCriteria();
  }

  function argsAutomaTurn()
  {
    return [
      'actions' => self::computeAutomaTurn(),
    ];

    // $actions = self::computeAutomaTurn();
    // $log = [];
    // $args = [];
    // foreach ($actions as $i => $act) {
    //   $name = 'action' . $i;
    //   $log[] = '${' . $name . '}';
    //   $args[$name] = self::getAutomaActionDesc($act['action'], $act['result']);
    // }
    //
    // return [
    //   'i18n' => ['actions'],
    //   'actions' => [
    //     'log' => join(',', $log),
    //     'args' => $args,
    //   ],
    // ];
  }

  /**
   * computeAutomaTurn(): given the automa cards, compute the list of actions the automa will take
   */
  function computeAutomaTurn()
  {
    $company = Companies::getActive();
    $nEngineers = $company->countAvailableEngineers();

    $actions = [];
    foreach ($this->getAutomaFlow() as $action) {
      if ($action['nEngineers'] > $nEngineers) {
        continue;
      }

      $res = $this->canAutomaTakeAction($action);
      if ($res !== false) {
        $actions[] = [
          'action' => $action,
          'result' => $res,
        ];
        $nEngineers -= $action['nEngineers'];

        // For these actions, the automa turn ends right away
        if (in_array($action['type'], [PRODUCE, CONSTRUCT, ROTATE_WHEEL, GAIN_MACHINE, PATENT])) {
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

    ///////////////////////////////////////////
    // Produce : must be able to produce + fulfill a contract + has a reason to gain energy on the track (see below)
    if ($type == PRODUCE) {
      return $this->canAutomaTakeProduceAction($company, $action);
    }
    ///////////////////////////////////////////
    // Place Droplet : only if it can reach automa's barrage
    elseif ($type == \PLACE_DROPLET) {
      return false; // TODO
    }
    ///////////////////////////////////////////
    // Construct : only if it has available machinery and tech tile (see below)
    elseif ($type == CONSTRUCT) {
      return $this->canAutomaTakeConstructAction($company, $action);
    }
    //////////////////////////////////////////
    // External Work : LWP
    elseif ($type == \EXTERNAL_WORK) {
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
      $condition = $action['condition'] ?? null;
      if ($condition == 'not_last_round' && Globals::getRound() == 5) {
        return false;
      }

      return true;
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
      return false; // TODO
    }
  }

  /**
   * automaTakeAction($action, $result)
   */
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
        EXTERNAL_WORK => 'TODO',
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
      $engineers = $company->placeEngineer($actionSpaceId, $nEngineers, $state);
      Notifications::placeEngineers($company, $engineers, $board);
    }

    ///////////////////////////////////////////
    // Produce
    if ($type == PRODUCE) {
    }
    ///////////////////////////////////////////
    // Place Droplet
    elseif ($type == \PLACE_DROPLET) {
    }
    ///////////////////////////////////////////
    // Construct
    elseif ($type == CONSTRUCT) {
      Engine::runAutoma($result['flow']);
    }
    //////////////////////////////////////////
    // External Work : LWP
    elseif ($type == \EXTERNAL_WORK) {
    }
    //////////////////////////////////////////
    // Rotate wheel
    elseif ($type == \ROTATE_WHEEL) {
    }
    //////////////////////////////////////////
    // Gain machine
    elseif ($type == \GAIN_MACHINE) {
    }
    ////////////////////////////////////////
    // Gain VP
    elseif ($type == \GAIN_VP) {
    }
    ////////////////////////////////////////
    // Discard contracts
    elseif ($type == \TAKE_CONTRACT) {
    }
    ////////////////////////////////////////
    // Patent for advanced tech tile
    elseif ($type == PATENT) {
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
    foreach ($system as $system) {
      $prod = $system['productions'][$system['nDroplets']];
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
      'system' => $system,
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
    $spaceId = $this->getAutomaStructureEmplacement($company, $structure, $spaceIds);

    // Now find the good tile for that spot
    $maxLvl = 0;
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

    return $maxPair;
  }
}
