<?php
namespace BRG\Actions;
use BRG\Managers\Meeples;
use BRG\Managers\Players;
use BRG\Managers\Companies;
use BRG\Core\Notifications;
use BRG\Core\Stats;
use BRG\Helpers\Utils;
use BRG\Core\Engine;

class RotateWheel extends \BRG\Models\Action
{
  public function getState()
  {
    return ST_ROTATE_WHEEL;
  }

  public function isDoable($company, $ignoreResources = false)
  {
    return true;
  }

  public function stRotateWheel()
  {
    $company = Companies::getActive();
    $ctxArgs = Engine::getNextUnresolved()->getArgs();

    for ($i = 0; $i < $ctxArgs['n']; $i++) {
      $company->rotateWheel();
    }
    $this->resolveAction(['n' => $ctxArgs['n']]);
  }
}
