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
use BRG\Core\Game;

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

  public function getCtxArgs()
  {
    return $this->ctx == null ? null : (is_array($this->ctx) ? $this->ctx : $this->ctx->getArgs());
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

  public static function fulfillExternalWorkAutoma($work)
  {
    $company = Companies::getActive();

    // Make it fulfilled
    $work->fulfill($company);
    Notifications::fulfillExtWork($company, $work);
    Stats::incExtWork($company, 1);
    $vp = $work->getVp();
    Stats::incVpWorks($company, $vp);

    $flow = $work->computeRewardFlow(true);
    $actions = Game::get()->convertFlowToAutomaActions($flow);
    Game::get()->automaTakeActions($actions);
  }

  public function fulfillExternalWork($work)
  {
    $company = Companies::getActive();

    // Make it fulfilled
    $work->fulfill($company);
    Notifications::fulfillExtWork($company, $work);
    Stats::incExtWork($company, 1);
    $vp = $work->getVp();
    Stats::incVpWorks($company, $vp);

    // Insert its flow as a child (or run it right now if it's an Automa)
    $isLeslie = $this->getCtxArgs()['leslie'] ?? false;

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
