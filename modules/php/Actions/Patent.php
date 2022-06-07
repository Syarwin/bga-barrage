<?php
namespace BRG\Actions;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Players;
use BRG\Managers\Companies;
use BRG\Core\Notifications;
use BRG\Core\Stats;
use BRG\Helpers\Utils;
use BRG\Core\Engine;

class Patent extends \BRG\Models\Action
{
  public function getState()
  {
    return \ST_PATENT;
  }

  public function isDoable($company, $ignoreResources = false)
  {
    return true;
  }

  public function stPatent()
  {
    $company = Companies::getActive();
    $args = $this->getCtxArgs();

    $tile = TechnologyTiles::get('patent_' . $args['position']);

    TechnologyTiles::DB()->update(['company_id' => $company->getId(), 'tile_location' => 'company']);
    Notifications::acquirePatent($company, TechnologyTiles::get($tile->getId()));
    $this->resolveAction([]);
  }
}
