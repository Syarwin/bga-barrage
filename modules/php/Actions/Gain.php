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

  public function isIndependent($player = null)
  {
    $args = $this->getCtxArgs();

    if ($this->ctx->forceConfirmation()) {
      return false;
    }

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

    // Create resources
    $meeples = [];
    foreach ($args as $resource => $amount) {
      if (in_array($resource, ['spaceId', 'cId'])) {
        continue;
      }
      if ($resource == VP) {
        $company->incScore($amount);
        for ($i = 0; $i < $amount; $i++) {
          $meeples[] = ['type' => $resource, 'ignore' => true];
        }
        Notifications::score($company, $amount, null, true);
      } elseif ($resource == ENERGY) {
        $tokens = $company->incEnergy($amount);
        for ($i = 0; $i < $amount; $i++) {
          $meeples[] = ['type' => $resource, 'ignore' => true];
        }

        Notifications::moveTokens(Meeples::getMany($tokens));
      } else {
        $meeples = array_merge($meeples, $company->createResourceInReserve($resource, $amount)->toArray());
      }
      // TODO $statName = 'inc' . ($source == null ? 'Board' : 'Cards') . ucfirst($resource);
      // Stats::$statName($player, $amount);
    }
    // Notify
    Notifications::gainResources($company, $meeples, $spaceId, $source);
    $this->resolveAction();
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

  public function isAutomatic($player = null)
  {
    return true;
  }
}
