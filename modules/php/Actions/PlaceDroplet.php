<?php
namespace BRG\Actions;
use BRG\Managers\Meeples;
use BRG\Managers\Players;
use BRG\Core\Notifications;
use BRG\Core\Stats;
use BRG\Helpers\Utils;
use BRG\Map;
use BRG\Core\Engine;
use BRG\Managers\Companies;

class PlaceDroplet extends \BRG\Models\Action
{
  public function getState()
  {
    return ST_PLACE_DROPLET;
  }

  public function isDoable($company, $ignoreResources = false)
  {
    return true;
  }

  public function isOptional()
  {
    return false;
  }


  public function stPlaceDroplet()
  {
    $args = $this->getCtxArgs();
    if (isset($args['autoDroplet']) && count($args['autoDroplet']) > 0) {
      $meeples = [];
      foreach ($args['autoDroplet'] as $d) {
        $meeples[] = ['type' => DROPLET, 'nbr' => $d['nb'], 'location' => $d['location']];
      }
      $created = Meeples::create($meeples);
      $droplets = Meeples::getMany($created);
      Notifications::addAutoDroplets(Companies::getActive(), $droplets->toArray());
      Map::addDroplets($droplets);
      $this->resolveAction(['created' => $created]);
    }
  }

  public function argsPlaceDroplet()
  {
    $ctxArgs = Engine::getNextUnresolved()->getArgs();
    $toFlow = $ctxArgs['flows'] ?? false;
    return [
      'i18n' => ['speed'],
      'speed' => $toFlow? clienttranslate('immediate flow') : clienttranslate('delayed flow'),
      'headstreams' => Map::getHeadstreams(),
      'flow' => $toFlow,
      'n' => $ctxArgs['n'] ?? 0
    ];
  }

  public function actPlaceDroplet($headstreams)
  {
    $args = $this->argsPlaceDroplet();
    $company = Companies::getActive();
    if (count($headstreams) > $args['n']) {
      throw new \BgaVisibleSystemException('Too many droplet sent. Should not happen');
    } elseif (empty($headstreams)) {
      throw new \BgaVisibleSystemException('You must add at least one droplet. Should not happen');
    }

    $meeples = [];
    foreach ($headstreams as $h) {
      $meeples[] = ['type' => DROPLET, 'location' => $h];
    }
    $created = Meeples::create($meeples);
    $droplets = Meeples::getMany($created);

    Notifications::addDroplets($company, $droplets->toArray(), Engine::getNextUnresolved()->getSpaceId());
    Map::addDroplets($droplets);

    if ($args['flow']) {
      Notifications::message(clienttranslate('Droplets are flowing'));
      Map::flowDroplets($droplets);
    }
    $this->resolveAction(['created' => $created]);
  }
}
