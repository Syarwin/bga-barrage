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

  public function getDescription($ignoreResources = false)
  {
    $n = $this->ctx->getArgs()['position'];
    return [
      'log' => clienttranslate('Take advanced tech tile n°${n}'),
      'args' => ['n' => $n],
    ];
  }

  public function isDoable($company, $ignoreResources = false)
  {
    return !is_null($this->getTile());
  }

  public function isAutomatic($company = null)
  {
    return true;
  }

  public function getTile()
  {
    $args = $this->getCtxArgs();
    $tile = TechnologyTiles::getFilteredQuery(null, 'patent_' . $args['position'])
      ->get()
      ->first();
  }

  public function stPatent()
  {
    $company = Companies::getActive();
    $tile = $this->getTile();

    TechnologyTiles::DB()->update(['company_id' => $company->getId(), 'tile_location' => 'company'], $tile->getId());
    Notifications::acquirePatent($company, TechnologyTiles::get($tile->getId()));
    Stats::incAdvTile($company, 1);
    $this->resolveAction([]);
  }
}
