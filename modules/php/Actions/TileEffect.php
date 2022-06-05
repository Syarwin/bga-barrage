<?php
namespace BRG\Actions;
use BRG\Managers\Meeples;
use BRG\Managers\Players;
use BRG\Core\Engine;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Companies;
use BRG\Core\Notifications;
use BRG\Core\Stats;
use BRG\Helpers\Utils;

class TileEffect extends \BRG\Models\Action
{
  public function getState()
  {
    return \ST_TILE_EFFECT;
  }

  public function argsTileEffect()
  {
    // TODO
    return [];
  }

  public function stTileEffect()
  {
    $args = $this->getCtxArgs();
    $tile = TechnologyTiles::get($args['tileId']);

    if (!$tile->isAutomatic()) {
      return;
    }

    $flow = $tile->getPowerFlow($args['slot']);
    if (!is_null($flow)) {
      Engine::insertAsChild($flow);
    }
  }

  public function actTileEffect()
  {
    // TODO
    return;
  }
}
