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

class TakeContract extends \BRG\Models\Action
{
  public function getState()
  {
    return \ST_TAKE_CONTRACT;
  }

  public function argsTakeContract()
  {
    $ctxArgs = Engine::getNextUnresolved()->getArgs();
    $n = $ctxArgs['n'] ?? 1;
    $contracts = Contracts::getSelectQuery()
      ->where('contract_location', 'LIKE', 'contract-stack%')
      ->where('type', '<>', '1')
      ->get()
      ->getIds();
    return ['n' => $n, 'contracts' => $contracts];
  }

  public function actTakeContract($contractIds)
  {
    $company = Companies::getActive();
    $args = $this->argsTakeContract();
    // check max contract
    if (count($contractIds) != $args['n']) {
      throw new \BgaVisibleSystemException('Not enough contract selected. Should not happen');
    }

    // check contracts are in the args
    if (count(array_diff($contractIds, $args['contracts'])) > 0) {
      throw new \feException('You cannot take those contracts. Should not happen');
    }

    Contracts::move($contractIds, 'hand_' . $company->getId());

    Notifications::pickContracts($company, Contracts::getMany($contractIds)->toArray());

    if ($company->getContracts(false)->count() > 3) {
      Engine::insertAsChild(['action' => DISCARD_CONTRACTS]);
    }
    $this->resolveAction(['contracts' => $contractIds]);
  }
}
