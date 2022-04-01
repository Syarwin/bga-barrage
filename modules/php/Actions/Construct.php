<?php
namespace BRG\Actions;
use BRG\Managers\Meeples;
use BRG\Managers\Players;
use BRG\Core\Notifications;
use BRG\Core\Stats;
use BRG\Helpers\Utils;

class Construct extends \BRG\Models\Action
{
  public function getState()
  {
    return \ST_CONSTRUCT;
  }
}
