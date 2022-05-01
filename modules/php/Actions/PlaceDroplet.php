<?php
namespace BRG\Actions;
use BRG\Managers\Meeples;
use BRG\Managers\Players;
use BRG\Core\Notifications;
use BRG\Core\Stats;
use BRG\Helpers\Utils;

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
}
