<?php
namespace BRG\Models;
use BRG\Managers\Farmers;
use BRG\Managers\Actions;
use BRG\Managers\Meeples;
use BRG\Managers\Fences;
use BRG\Managers\PlayerCards;
use BRG\Core\Engine;
use BRG\Core\Globals;
use BRG\Core\Notifications;
use BRG\Core\Preferences;
use BRG\Actions\Pay;
use BRG\Actions\Reorganize;
use BRG\Helpers\Utils;

/*
 * Company: all utility functions concerning a player, real or not
 */

class Company extends \BRG\Helpers\DB_Model
{
  protected $table = 'companies';
  protected $primary = 'id';
  protected $attributes = [
    'id' => ['id', 'int'],
    'no' => ['no', 'int'],
    'pId' => ['player_id', 'int'],
    'name' => 'name',
    'score' => ['score', 'int'],
    'scoreAux' => ['score_aux', 'int'],
  ];

  protected $id;
  protected $no;
  protected $name;
  protected $pId;
  protected $score = 0;
  protected $scoreAux = 0;

  public function __construct($row)
  {
    if ($row != null) {
      parent::__construct($row);
    }
  }

  public function jsonSerialize($currentPlayerId = null)
  {
    $current = $this->id == $currentPlayerId;
    $data = [
      'id' => $this->id,
      'pId' => $this->pId,
      'ai' => $this->isAI(),
      'lvl' => $this->getLvlAI(),
      'no' => $this->no,
      'name' => $this->name,
      'score' => $this->score,
      'score' => $this->scoreAux,
      'resources' => [],
    ];

    /*
    foreach (RESOURCES as $resource) {
      $data['resources'][$resource] = $this->countReserveResource($resource);
    }
*/
    return $data;
  }

  public function isAI()
  {
    return $this->pId < 0;
  }

  public function getLvlAI()
  {
    return $this->isAI() ? ($this->pId + 15) % 3 : null;
  }
}
