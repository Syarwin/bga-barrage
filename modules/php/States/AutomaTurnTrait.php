<?php
namespace BRG\States;
use BRG\Core\Globals;
use BRG\Core\Notifications;
use BRG\Managers\Players;
use BRG\Managers\Companies;
use BRG\Managers\Meeples;
use BRG\Managers\Fences;
use BRG\Managers\Actions;
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
    $this->computeAutomaTurn();
  }

  function getAutomaFlow()
  {
    return AutomaCards::getUiData()['front']->getFlow();
  }

  function getAutomaCriteria()
  {
    return AutomaCards::getUiData()['back']->getCriteria();
  }

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

    var_dump($actions);
  }

  function canAutomaTakeAction($action)
  {
    $company = Companies::getActive();
    $type = $action['type'];

    ///////////////////////////////////////////
    // Produce : must be able to produce + fulfill a contract + has a reason to gain energy on the track
    if ($type == PRODUCE) {
      return $this->canAutomaTakeProduceAction($company, $action);
    }
    ///////////////////////////////////////////
    // Place Droplet : only if it can reach automa's barrage
    elseif ($type == \PLACE_DROPLET) {
      return false;
    }
    ///////////////////////////////////////////
    // Construct : only if it has available machinery and tech tile
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
      return false;
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

    // Can we complete a production system ?
    if (count($spaceIds) > 1 && $structure != \ELEVATION) {
      $almostComplete = Map::getAlmostCompleteProductionSystems($company, $structure);
      $spaces = $spaceIds;
      Utils::filter($spaces, function ($spaceId) use ($structure, $almostComplete) {
        return $this->canCompleteSystem($structure, $spaceId, $almostComplete);
      });
      if (!empty($spaces)) {
        $spaceIds = $spaces;
      }
    }

    if (count($spaceIds) > 1) {
      var_dump($spaceIds);
      $criteria = $this->getAutomaCriteria()[$structure];
      var_dump($criteria);
      die('todo : tiebreaker for construct');
    }

    // Use criteria to reduce the possible choice
    while (count($spaceIds) > 1) {
    }

    // TODO
    if (count($pairs) > 1) {
      die('todo : tiebreaker for construct');
    }

    return false;
  }

  public function canCompleteSystem($structure, $spaceId, $almostCompleteSystems)
  {
    foreach ($almostCompleteSystems as $system) {
      if ($structure == BASE && $spaceId == $system['basin']) {
        return true;
      } elseif ($structure == CONDUIT && $spaceId == $system['conduitSpaceId']) {
        return true;
      } elseif ($structure == POWERHOUSE && startsWith($spaceId, $system['powerhouseSpaceId'])) {
        return true;
      }
    }
    return false;
  }
}

function startsWith($haystack, $needle)
{
  $length = strlen($needle);
  return substr($haystack, 0, $length) === $needle;
}
/*
[
  'nEngineers' => 2,
  'type' => \PRODUCE,
  'contract' => \CONTRACT_GREEN,
  'bonus' => -2,
],
[
  'nEngineers' => 1,
  'type' => \PLACE_DROPLET,
  'n' => 2,
  'flow' => false,
],
[
  'nEngineers' => 2,
  'type' => \CONSTRUCT,
  'structure' => BASE,
],
[
  'nEngineers' => 2,
  'type' => \CONSTRUCT,
  'structure' => ELEVATION,
],
[
  'nEngineers' => 2,
  'type' => EXTERNAL_WORK,
  'order' => [1, 2, 3],
],
[
  'nEngineers' => 1,
  'type' => \ROTATE_WHEEL,
  'n' => 1,
],
[
  'nEngineers' => 1,
  'type' => GAIN_MACHINE,
  'vp' => -3,
  'condition' => 'not_last_round',
],
[
  'nEngineers' => 1,
  'type' => GAIN_VP,
  'vp' => 1,
],
*/
