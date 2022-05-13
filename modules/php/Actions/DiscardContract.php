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

class DiscardContract extends \BRG\Models\Action
{
  public function getState()
  {
    return \ST_DISCARD_CONTRACT;
  }

  public function argsDiscardContract()
  {
    $contracts = Companies::getActive()->getContracts(false);
    return ['nb' => $contracts->count() - 3, 'contracts' => $contracts->getIds()];
  }

  public function actDiscardContract($contractIds)
  {
    $company = Companies::getActive();
    $args = $this->argsDiscardContract();
    // check max contract
    if (count($contractIds) != $args['nb']) {
      throw new \BgaVisibleSystemException('Not enough contract selected. Should not happen');
    }

    // check contracts are in the args
    if (count(array_diff($contractIds, $args['contracts'])) > 0) {
      throw new \feException('You cannot discard those contracts. Should not happen');
    }

    foreach ($contractIds as $cId) {
      Contracts::DB()->delete($cId);
    }

    Notifications::discardContracts($company, $contractIds);

    // $this->resolveAction([]);
    $this->resolveAction(['discardedContracts' => $contractIds]);
  }
}
