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

  protected function getFulfillableContracts($company, $energy)
  {
    $noReduction = $this->getCtxArgs()['noReduction'] ?? false;
    return $company
      ->getAvailableContracts()
      ->merge(Contracts::getNationalContracts())
      ->filter(function ($contract) use ($company, $energy, $noReduction) {
        return $contract->getCost() <= $energy + ($noReduction ? 0 : $company->getContractReduction());
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
    $noReduction = $this->getCtxArgs()['noReduction'] ?? false;

    return [
      'n' => $energy + ($noReduction ? 0 : $company->getContractReduction()),
      'contractIds' => $this->getFulfillableContracts($company, $energy)->getIds(),
      'descSuffix' => $company->isXO(XO_SIMONE) ? 'simone' : '',
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

    $this->fulfillContract($contract);
    $this->resolveAction(['resolvedContract' => $contractId]);
  }

  public function fulfillContract($contract)
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
      Engine::insertAsChild($flow);
    }
  }
}
