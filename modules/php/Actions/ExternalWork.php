<?php
namespace BRG\Actions;
use BRG\Managers\ExternalWorks;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Players;
use BRG\Managers\Companies;
use BRG\Core\Notifications;
use BRG\Core\Stats;
use BRG\Helpers\Utils;
use BRG\Core\Engine;
use BRG\Core\Globals;

class ExternalWork extends \BRG\Models\Action
{
  public function getState()
  {
    return \ST_EXTERNAL_WORK;
  }

  public function getDescription($ignoreResources = false)
  {
    $n = $this->ctx->getArgs()['position'];
    if ($this->getCostType() == CREDIT) {
      $cost = $this->getWork()->getCost(CREDIT);
      return [
        'log' => clienttranslate('Buy external work n°${n} for ${m}<CREDIT>'),
        'args' => [
          'n' => $n,
          'm' => $cost[CREDIT],
        ],
      ];
    } else {
      return [
        'log' => clienttranslate('Fulfill external work n°${n}'),
        'args' => ['n' => $n],
      ];
    }
  }

  public function isDoable($company, $ignoreResources = false)
  {
    return $this->getWork() != null && $company->canPayCost($this->getWork()->getCost($this->getCostType()));
  }

  public function getCostType()
  {
    return $this->getCtxArgs()['cost'] ?? null;
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
    $isLeslie = $this->getCtxArgs()['leslie'] ?? false;

    // Insert its flow as a child (or run it right now if it's an Automa)
    if ($isAI) {
      $flow = $work->computeRewardFlow($isAI);
      $actions = Game::get()->convertFlowToAutomaActions($flow);
      Game::get()->automaTakeActions($actions);
    } else {
      $payNode = [
        'action' => PAY,
        'args' => [
          'nb' => 1,
          'costs' => Utils::formatCost($work->getCost($this->getCostType())),
          'source' => clienttranslate('External Work Cost'),
        ],
      ];
      if ($isLeslie) {
        $payNode['args']['target'] = 'wheel';
        if (Globals::getMahiriPower() != XO_LESLIE) {
          $payNode['args']['tileId'] = TechnologyTiles::getLeslie()->getId();
        }
      }
      Engine::insertAsChild($payNode);

      if ($isLeslie) {
        Engine::insertAsChild([
          'action' => \ROTATE_WHEEL,
          'args' => ['n' => 1],
        ]);
      }

      $flow = $work->computeRewardFlow();
      Engine::insertAsChild($flow);
    }
  }
}
