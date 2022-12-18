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

  public function isAutomatic($company = null)
  {
    return false;
  }

  public function isDoable($company, $ignoreResources = false)
  {
    return count($this->getChoices($company)) > 0;
  }

  public function getType()
  {
    $args = $this->getCtxArgs();
    return $args['type'];
  }

  public function getChoices($company)
  {
    $type = $this->getType();
    if ($type == \TECH_TILE) {
      return $company->getWheelTiles()->getIds();
    } elseif ($type == \ANY_MACHINE) {
      $choices = [];
      for ($slot = 0; $slot < 6; $slot++) {
        $mIds = Meeples::getOnWheel($company->getId(), $slot)->getIds();
        if (!empty($mIds)) {
          $choices[$slot] = $mIds;
        }
      }

      return $choices;
    }
  }

  public function argsRetrieveFromWheel()
  {
    $company = Companies::getActive();
    $type = $this->getType();
    return [
      'type' => $type,
      'choices' => $this->getChoices($company),
      'descSuffix' => $type == \TECH_TILE ? '' : 'machines',
      'cId' => $company->getId(),
    ];
  }

  public function actRetrieveTile($tileId)
  {
    // Sanity checks
    self::checkAction('actRetrieveTile');
    $tileIds = $this->argsRetrieveFromWheel()['choices'];
    if (!in_array($tileId, $tileIds)) {
      throw new \BgaVisibleSystemException('You cant retrieve that tile. Should not happen');
    }

    // Return back the tile
    $company = Companies::getActive();
    TechnologyTiles::move($tileId, 'company');

    Notifications::recoverResources($company, [], TechnologyTiles::getSingle($tileId));

    $this->resolveAction(['tileId' => $tileId]);
  }

  public function actRetrieveMachines($slot, $machines)
  {
    // Sanity checks
    self::checkAction('actRetrieveMachines');
    $meepleIds = $this->argsRetrieveFromWheel()['choices'][$slot] ?? null;
    if (is_null($meepleIds)) {
      throw new \BgaVisibleSystemException('You cant retrieve that slot. Should not happen');
    }
    foreach ($machines as $mId) {
      if (!in_array($mId, $meepleIds)) {
        throw new \BgaVisibleSystemException('You cant retrieve that machine. Should not happen');
      }
    }

    $company = Companies::getActive();
    Meeples::move($machines, 'reserve');

    Notifications::recoverResources($company, Meeples::getMany($machines), null);

    $this->resolveAction(['meepleIds' => $machines]);
  }
}
