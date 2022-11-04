<?php
namespace BRG\Actions;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Players;
use BRG\Managers\Companies;
use BRG\Core\Notifications;
use BRG\Core\Stats;
use BRG\Helpers\Utils;
use BRG\Core\Engine;

class ExternalWork extends \BRG\Models\Action
{
  public function getState()
  {
    return \ST_EXTERNAL_WORK;
  }

  public function isDoable($company, $ignoreResources = false)
  {
    return true;
  }

  public function isAutomatic($company = null)
  {
    return true;
  }

  public function stExternalWork()
  {
    $company = Companies::getActive();
    $args = $this->getCtxArgs();

    // $tile = TechnologyTiles::getFilteredQuery(null, 'patent_' . $args['position'])
    //   ->get()
    //   ->first();

    // TechnologyTiles::DB()->update(['company_id' => $company->getId(), 'tile_location' => 'company'], $tile->getId());
    // Notifications::acquirePatent($company, TechnologyTiles::get($tile->getId()));
    // Stats::incAdvTile($company, 1);
    // $this->resolveAction([]);
  }
}
