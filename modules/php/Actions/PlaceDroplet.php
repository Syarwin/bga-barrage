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

  public function getPossibleSpaces($company)
  {
    $ctxArgs = $this->getCtxArgs();
    $isDam = ($ctxArgs['type'] ?? null) == 'dam';
    if (!$isDam) {
      return Map::getHeadstreams();
    } else {
      $constraint = $ctxArgs['constraint'] ?? null;
      $dams = Map::getUnfullDams($company);
      Utils::filter($dams, function ($dam) use ($constraint) {
        return $dam != $constraint;
      });
      return $dams;
    }
  }

  public function argsPlaceDroplet()
  {
    $ctxArgs = $this->getCtxArgs();
    $toFlow = $ctxArgs['flows'] ?? false;
    $isDam = ($ctxArgs['type'] ?? null) == 'dam';
    $company = Companies::getActive();
    return [
      'i18n' => ['speed'],
      'speed' => $isDam
        ? clienttranslate('neutral or player dam')
        : ($toFlow
          ? clienttranslate('immediate flow')
          : clienttranslate('delayed flow')),
      'spaces' => $this->getPossibleSpaces($company),
      'flow' => $toFlow,
      'isDam' => $isDam,
      'n' => $ctxArgs['n'] ?? 0,
    ];
  }

  public function actPlaceDroplet($spaces)
  {
    $args = $this->argsPlaceDroplet();
    $company = Companies::getActive();
    if (count($spaces) > $args['n']) {
      throw new \BgaVisibleSystemException('Too many droplet sent. Should not happen');
    } elseif (empty($spaces)) {
      throw new \BgaVisibleSystemException('You must add at least one droplet. Should not happen');
    }
    foreach ($spaces as $sId) {
      if (!in_array($sId, $args['spaces'])) {
        throw new \BgaVisibleSystemException('Cannot place droplet here. Should not happen');
      }
    }

    $spaceId = Engine::getNextUnresolved()->getSpaceId();
    $created = $this->placeDroplets($company, $spaces, $spaceId, $args['flow']);
    $this->resolveAction(['created' => $created]);
  }

  public function placeDroplets($company, $headstreams, $spaceId, $flowing)
  {
    $meeples = [];
    foreach ($headstreams as $h) {
      $meeples[] = ['type' => DROPLET, 'location' => $h];
    }
    $created = Meeples::create($meeples);
    $droplets = Meeples::getMany($created);

    Notifications::addDroplets($company, $droplets->toArray(), $spaceId, $flowing, $headstreams);
    Map::addDroplets($droplets);

    if ($flowing) {
      Map::flowDroplets($droplets);
    }
    return $created;
  }
}
