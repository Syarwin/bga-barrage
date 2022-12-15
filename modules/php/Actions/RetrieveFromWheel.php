<?php
namespace BRG\Actions;
use BRG\Managers\Meeples;
use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Core\Notifications;
use BRG\Core\Stats;
use BRG\Helpers\Utils;

class RetrieveFromWheel extends \BRG\Models\Action
{
  public function getState()
  {
    return ST_RETRIEVE_FROM_WHEEL;
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

  public function isAutomatic($company = null)
  {
    return false;
  }

  public function isDoable($company, $ignoreResources = false)
  {
    return $company->getWheelTiles()->count() > 0;
  }

  public function argsRetrieveFromWheel()
  {
    $company = Companies::getActive();
    return [
      'tileIds' => $company->getWheelTiles()->getIds(),
    ];
  }

  public function actRetrieveTile($tileId)
  {
    // Sanity checks
    self::checkAction('actRetrieveTile');
    $tileIds = $this->argsRetrieveFromWheel()['tileIds'];

    if (!in_array($tileId, $tileIds)) {
      throw new \BgaVisibleSystemException('You cant retrieve that tile. Should not happen');
    }

    // Return back the tile
    $company = Companies::getActive();
    TechnologyTiles::move($tileId, 'company');

    Notifications::recoverResources($company, [], TechnologyTiles::getSingle($tileId));

    $this->resolveAction(['tileId' => $tileId]);
  }
}
