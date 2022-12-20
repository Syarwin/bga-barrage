<?php
namespace BRG\Actions;
use BRG\Managers\Meeples;
use BRG\Managers\Players;
use BRG\Managers\Contracts;
use BRG\Managers\Companies;
use BRG\Core\Notifications;
use BRG\Core\Stats;
use BRG\Core\Engine;
use BRG\Core\Game;
use BRG\Helpers\Utils;

class FulfillContract extends \BRG\Models\Action
{
  public function getState()
  {
    return \ST_FULFILL_CONTRACT;
  }

  protected function getFulfillableContracts($company)
  {
    $contracts = $company->getAvailableContracts();
    if ($this->isProduction()) {
      $contracts = $contracts->merge(Contracts::getNationalContracts());
    }
    $energy = $this->getEnergy($company);
    if ($energy > 0) {
      $contracts = $contracts->filter(function ($contract) use ($company, $energy) {
        return $contract->getCost() <= $energy;
      });
    }

    return $contracts;
  }

  protected function isProduction()
  {
    return $this->getCtxArgs()['production'] ?? false;
  }

  protected function getEnergy($company)
  {
    return ($this->getCtxArgs()['n'] ?? 0) + ($this->isProduction() ? $company->getContractReduction() : 0);
  }

  public function isDoable($company, $ignoreResources = false)
  {
    $energy = $this->getEnergy($company);
    return !$this->getFulfillableContracts($company)->empty();
  }

  public function argsFulfillContract()
  {
    $company = Companies::getActive();
    $n = $this->getEnergy($company);

    return [
      'n' => $n,
      'contractIds' => $this->getFulfillableContracts($company)->getIds(),
      'costs' => $this->getFulfillableContracts($company)->map(function ($contract) {
        return $contract->getCost();
      }),
      'descSuffix' => $n < 0 ? 'nolimit' : ($this->isProduction() && $company->isXO(XO_SIMONE) ? 'simone' : ''),
    ];
  }

  public function actFulfillContract($contractId)
  {
    // Sanity checks
    self::checkAction('actFulfillContract');
    $company = Companies::getActive();
    $energy = $this->getEnergy($company);
    $contracts = $this->getFulfillableContracts($company);
    $contract = $contracts[$contractId] ?? null;
    if (is_null($contract)) {
      throw new \feException('You cannot fulfill this contract. Should not happen');
    }

    $this->fulfillContract($contract);
    $this->resolveAction(['resolvedContract' => $contractId]);
  }

  public function fulfillContract($contract, $simone = false)
  {
    $company = Companies::getActive();
    $isAI = $company->isAI();

    // Make it fulfilled
    $contract->fulfill($company);
    Notifications::fulfillContract($company, $contract);
    Stats::incContract($company, 1);
    $vp = $contract->getVp();
    Stats::incVpContracts($company, $vp);

    // Insert its flow as a child (or run it right now if it's an Automa)
    if ($isAI) {
      $flow = $contract->computeRewardFlow($isAI);
      $actions = Game::get()->convertFlowToAutomaActions($flow);
      Game::get()->automaTakeActions($actions);
    } else {
      $flow = $contract->computeRewardFlow();
      if ($simone) {
        return $flow;
      } else {
        Engine::insertAsChild($flow);
      }
    }
  }

  public function actFulfillContractSimone($contractIds)
  {
    // Sanity checks
    self::checkAction('actFulfillContractSimone');
    $company = Companies::getActive();
    if (!$company->isXO(XO_SIMONE) || !$this->isProduction()) {
      throw new \feException('You cannot fulfill several contract at once. Should not happen');
    }

    $energy = $this->getEnergy($company);
    $contracts = $this->getFulfillableContracts($company);
    $totalCost = 0;
    $childs = [];
    foreach ($contractIds as $contractId) {
      $contract = $contracts[$contractId] ?? null;
      if (is_null($contract) || $totalCost + $contract->getCost() > $energy) {
        throw new \feException('You cannot fulfill this contract. Should not happen');
      }

      $flow = $this->fulfillContract($contract, true);
      $childs = array_merge($childs, $flow['childs']);
    }

    Engine::insertAsChild([
      'type' => NODE_SEQ,
      'childs' => $childs,
    ]);

    $this->resolveAction(['resolvedContracts' => $contractIds]);
  }
}
