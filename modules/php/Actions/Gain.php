<?php
namespace BRG\Actions;
use BRG\Managers\Meeples;
use BRG\Managers\Players;
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
    foreach ($args as $resource => $amount) {
      if (\in_array($resource, [SHEEP, PIG, CATTLE])) {
        return false;
      }
    }

    if ($this->ctx->forceConfirmation()) {
      return false;
    }

    return true;
  }

  public function getPlayer()
  {
    $args = $this->getCtxArgs();
    $pId = $args['pId'] ?? Players::getActiveId();
    return Players::get($pId);
  }

  public function stGain()
  {
    die("todo");

    /*
    $player = $this->getPlayer();
    $args = $this->getCtxArgs();
    $cardId = $this->ctx->getCardId();
    $source = $this->ctx->getSource();

    // Create resources
    $meeples = [];
    foreach ($args as $resource => $amount) {
      if (in_array($resource, ['cardId', 'skipReorganize', 'pId'])) {
        continue;
      }
      $meeples = array_merge($meeples, $player->createResourceInReserve($resource, $amount));
      $statName = 'inc' . ($source == null ? 'Board' : 'Cards') . ucfirst($resource);
      Stats::$statName($player, $amount);

      if ($resource == GRAIN && $player->hasPlayedCard('C86_LivestockFeeder')) {
        Notifications::updateDropZones($player);
      }
    }

    // Auto reorganize if needed (return true if need to enter the state to confirm)
    $reorganize = $player->checkAutoReorganize($meeples);
    // Notify
    Notifications::gainResources($player, $meeples, $cardId, $source);
    $player->updateObtainedResources($meeples);
    if (!($args['skipReorganize'] ?? false)) {
      $player->checkAnimalsInReserve($reorganize);
    }
    $this->resolveAction();
    */
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
