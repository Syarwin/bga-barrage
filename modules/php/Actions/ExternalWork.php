<?php
namespace BRG\Actions;
use BRG\Managers\ExternalWorks;
use BRG\Managers\Players;
use BRG\Managers\Companies;
use BRG\Core\Notifications;
use BRG\Core\Stats;
use BRG\Helpers\Utils;
use BRG\Core\Engine;

class ExternalWork extends \BRG\Models\Action
{
  public function getState()
  {
    return \ST_EXTERNAL_WORK;
  }

  public function isDoable($company, $ignoreResources = false)
  {
    return $company->canPayCost($this->getWork()->getCost());
  }

  public function isAutomatic($company = null)
  {
    return true;
  }

  protected function getWork()
  {
    $args = $this->getCtxArgs();
    return ExternalWorks::getFilteredQuery(null, 'work_' . $args['position'])
      ->get()
      ->first();
  }

  public function stExternalWork()
  {
    $work = $this->getWork();
    $this->fulfillExternalWork($work);
    $this->resolveAction([]);
  }

  public function fulfillExternalWork($work)
  {
    $company = Companies::getActive();
    $isAI = $company->isAI();

    // Make it fulfilled
    $work->fulfill($company);
    Notifications::fulfillExtWork($company, $work);
    Stats::incExtWork($company, 1);
    $vp = $work->getVp();
    Stats::incVpWorks($company, $vp);

    // Insert its flow as a child (or run it right now if it's an Automa)
    if ($isAI) {
      $flow = $work->computeRewardFlow($isAI);
      $actions = Game::get()->convertFlowToAutomaActions($flow);
      Game::get()->automaTakeActions($actions);
    } else {
      Engine::insertAsChild([
        'action' => PAY,
        'args' => [
          'nb' => 1,
          'costs' => Utils::formatCost($work->getCost()),
          'source' => clienttranslate('External Work Cost'),
        ],
      ]);
      $flow = $work->computeRewardFlow();
      Engine::insertAsChild($flow);
    }
  }
}