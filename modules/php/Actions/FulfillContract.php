<?php
namespace BRG\Actions;
use BRG\Managers\Meeples;
use BRG\Managers\Players;
use BRG\Core\Engine;
use BRG\Managers\Contracts;
use BRG\Managers\Companies;
use BRG\Core\Notifications;
use BRG\Core\Stats;
use BRG\Helpers\Utils;

class FulfillContract extends \BRG\Models\Action
{
  public function getState()
  {
    return \ST_FULFILL_CONTRACT;
  }

  protected function getFulfillableContracts($company, $energy)
  {
    return $company
      ->getAvailableContracts()
      ->merge(Contracts::getNationalContracts())
      ->filter(function ($contract) use ($company, $energy) {
        return $contract->getCost() - $company->getContractReduction() <= $energy;
      });
  }

  protected function getEnergy()
  {
    return $this->getCtxArgs()['energy'] ?? 0;
  }

  public function isDoable($company, $ignoreResources = false)
  {
    $energy = $this->getEnergy();
    return !$this->getFulfillableContracts($company, $energy)->empty();
  }

  public function argsFulfillContract()
  {
    $company = Companies::getActive();
    $energy = $this->getEnergy();

    return [
      'n' => $energy + $company->getContractReduction(),
      'contractIds' => $this->getFulfillableContracts($company, $energy)->getIds(),
    ];
  }

  public function actFulfillContract($contractId)
  {
    // Sanity checks
    self::checkAction('actFulfillContract');
    $company = Companies::getActive();
    $energy = $this->getEnergy();
    $contracts = $this->getFulfillableContracts($company, $energy);
    $contract = $contracts[$contractId] ?? null;
    if (is_null($contract)) {
      throw new \feException('You cannot fulfill this contract. Should not happen');
    }

    // Make it fulfilled
    $contract->fulfill($company);
    Notifications::fulfillContract($company, $contract);

    // Insert its flow as a child
    $flow = $contract->computeRewardFlow();
    Engine::insertAsChild($flow);

    $this->resolveAction(['resolvedContract' => $contractId]);
  }
}
