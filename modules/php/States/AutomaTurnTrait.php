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
use BRG\Map;

trait AutomaTurnTrait
{
  function stPreAutomaTurn()
  {
    AutomaCards::flip();

    $this->gamestate->nextState();
  }

  function computeAutomaTurn()
  {
    $company = Companies::getActive();
    $cards = AutomaCards::getUiData();
    $criteria = $cards['back'];

    $card = $cards['front'];
    $nEngineers = $company->countAvailableEngineers();

    $actions = [];
    foreach ($card->getFlow() as $action) {
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
    ///////////////////////////////////////////
    // Place Droplet : only if it can reach automa's barrage
    elseif ($type == \PLACE_DROPLET) {
      return false;
    }
    ///////////////////////////////////////////
    // Construct : only if it has available machinery and tech tile
    elseif ($type == CONSTRUCT) {
      return false;
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
