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
    $args = $this->getCtxArgs();
    $n = $args['n'] ?? 1;
    $contracts = Contracts::getAvailableToTake();

    return [
      'n' => $n,
      'contractIds' => $contracts->getIds(),
    ];
  }

  public function actTakeContract($contractIds)
  {
    // Sanity checks
    self::checkAction('actTakeContract');
    $company = Companies::getActive();
    $args = $this->argsTakeContract();
    // check max contract
    if (count($contractIds) != $args['n']) {
      throw new \BgaVisibleSystemException('Incorrect number of contracts selected. Should not happen');
    }

    // check contracts are in the args
    if (count(array_diff($contractIds, $args['contractIds'])) > 0) {
      throw new \feException('You cannot take those contracts. Should not happen');
    }

    // Move them and notify
    Contracts::move($contractIds, ['hand', $company->getId()]);
    Notifications::pickContracts($company, Contracts::getMany($contractIds)->toArray());

    // If too many contract in hand => must discard
    $maxLimit = $company->isXO(\XO_SIMONE) ? 4 : 3;
    if ($company->getContracts(false)->count() > $maxLimit) {
      Engine::insertAsChild(['action' => DISCARD_CONTRACTS]);
    }
    $this->resolveAction(['contracts' => $contractIds]);
  }
}
