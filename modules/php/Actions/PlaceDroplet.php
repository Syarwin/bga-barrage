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

  public function stPlaceDroplet()
  {
    // throw new \feException('titi');
  }

  public function argsPlaceDroplet()
  {
    throw new \feException(print_r(Engine::getNextUnresolved()->getArgs()));
    $ctxArgs = Engine::getNextUnresolved()->getArgs();
    $toFlow = $ctxArgs['flows'] ?? false;
    return ['headstreams' => Map::getHeadstreams(), 'flow' => $toFlow, 'number' => $ctxArgs['n']];
  }

  public function actPlaceDroplet($headstreams)
  {
    $args = $this->argsPlaceDroplet();
    $company = Companies::getActive();
    if (count($headstreams) > $args['number']) {
      throw new \BgaVisibleSystemException('Too many droplet sent. Should not happen');
    } elseif (empty($headstream)) {
      throw new \BgaVisibleSystemException('You must add at least one droplet. Should not happen');
    }

    $meeples = [];
    foreach ($headstreams as $h) {
      $meeples[] = ['type' => DROPLET, 'location' => $h];
    }
    $created = Meeples::create($meeples);

    Notifications::addDroplets(
      $company,
      Meeples::getMany($created)->toArray(),
      Engine::getNextUnresolved()->getSpaceId()
    );

    if ($args['flow'] === true) {
      Notifications::message('Droplet is flowing');
      Map::flow($created[0]);
    }
    $this->resolveAction(['created' => $created]);
  }
}
