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

  public function argsFulfillContract()
  {
    $ctxArgs = Engine::getNextUnresolved()->getArgs();
    $energy = $ctxArgs['energy'] ?? 0;
    $company = Companies::getActive();

    $contracts = $company->getContracts(false)->merge(Contracts::getNationalContracts());
    $solvableContracts = [];
    foreach ($contracts as $cId => $contract) {
      if ($contract->getCost() - $company->getContractReduction() <= $energy) {
        $solvableContracts[] = $cId;
      }
    }
    return ['contracts' => $solvableContracts];
  }

  public function stFulfillContract()
  {
    if (empty($this->argsFulfillContract()['contracts'])) {
      $this->resolveAction([]);
    }
  }

  public function actFulfillContract($contractId)
  {
    $company = Companies::getActive();
    $args = $this->argsFulfillContract();

    // check contracts are in the args
    if (!in_array($contractId, $args['contracts'])) {
      throw new \feException('You cannot fulfill this contract. Should not happen');
    }
    $oContract = Contracts::get($contractId);
    Engine::insertAsChild($oContract->fulfill($company));
    Notifications::fulfillContract($company, Contracts::get($contractId));

    $this->resolveAction(['resolvedContract' => $contractId]);
  }
}
