<?php

namespace BRG\Actions;

use BRG\Managers\Meeples;
use BRG\Managers\Companies;
use BRG\Core\Notifications;
use BRG\Core\Stats;
use BRG\Helpers\Utils;

class Gain extends \BRG\Models\Action
{
  public function getState()
  {
    return ST_GAIN;
  }

  public function getDescription($ignoreResources = false)
  {
    return [
      'log' => clienttranslate('Gain ${resources_desc}'),
      'args' => [
        'resources_desc' => Utils::resourcesToStr($this->ctx->getArgs()),
      ],
    ];
  }

  public function isIndependent($company = null)
  {
    return true;
  }

  public function isAutomatic($company = null)
  {
    return true;
  }

  public function getCompany()
  {
    $args = $this->getCtxArgs();
    $cId = $args['cId'] ?? Companies::getActiveId();
    return Companies::get($cId);
  }

  public function stGain()
  {
    $company = $this->getCompany();
    $args = $this->getCtxArgs();
    $spaceId = $this->ctx->getSpaceId();
    $source = $this->ctx->getSource();

    $this->gainResources($company, $args, $spaceId, $source);
    $this->resolveAction();
  }

  public static function gainResources($company, $args, $spaceId = null, $source = null)
  {
    // Create resources
    $meeples = [];
    foreach ($args as $resource => $amount) {
      if (in_array($resource, ['spaceId', 'cId'])) {
        continue;
      }
      if ($resource == VP || ($resource == CREDIT && $company->isAI())) {
        for ($i = 0; $i < $amount; $i++) {
          $meeples[] = ['type' => VP, 'ignore' => true];
        }
        $company->incScore($amount, null, true);
      } elseif ($resource == ENERGY) {
        $company->incEnergy($amount, true);
        for ($i = 0; $i < $amount; $i++) {
          $meeples[] = ['type' => $resource, 'ignore' => true];
        }
      } else {
        if ($amount != 0) {
          $meeples = array_merge($meeples, $company->createResourceInReserve($resource, $amount)->toArray());
        }
      }
      // TODO $statName = 'inc' . ($source == null ? 'Board' : 'Cards') . ucfirst($resource);
      // Stats::$statName($player, $amount);
    }
    // Notify
    Notifications::gainResources($company, $meeples, $spaceId, $source);
  }
}
