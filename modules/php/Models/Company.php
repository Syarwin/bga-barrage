<?php
namespace BRG\Models;
use BRG\Managers\Farmers;
use BRG\Managers\Actions;
use BRG\Managers\Meeples;
use BRG\Managers\Fences;
use BRG\Managers\PlayerCards;
use BRG\Managers\Contracts;
use BRG\Core\Engine;
use BRG\Core\Globals;
use BRG\Core\Notifications;
use BRG\Core\Preferences;
use BRG\Actions\Pay;
use BRG\Actions\Reorganize;
use BRG\Helpers\Utils;
use BRG\Managers\TechnologyTiles;

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
    'officerId' => ['officer', 'int'],
    'energy' => ['energy', 'int'],
    'slot' => ['wheel_slot', 'int'],
  ];

  protected $id;
  protected $no;
  protected $name;
  protected $pId;
  protected $score = 0;
  protected $scoreAux = 0;
  protected $officer = null;
  protected $energy = 0;
  protected $slot = 0;

  protected $cname;
  protected $staticAttributes = ['cname', 'revenueBoard'];
  protected $revenueBoard = [BASE => [], \ELEVATION => [], CONDUIT => [], \POWERHOUSE => []];

  public function __construct($row)
  {
    if ($row != null) {
      parent::__construct($row);

      if ($this->officerId != 0) {
        $this->officer = Officers::getInstance($this->officerId, $this);
      }
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
      'scoreAux' => $this->scoreAux,
      'energy' => $this->energy,
      'resources' => [],
      'wheel' => $this->getWheel(),
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

  public function isXO($xId)
  {
    return false;
  }

  public function canTakeAction($action, $ctx, $ignoreResources)
  {
    return Actions::isDoable($action, $ctx, $this, $ignoreResources);
  }

  /////////////////////////////////////////////////////
  //  ____
  // |  _ \ ___  ___  ___  _   _ _ __ ___ ___  ___
  // | |_) / _ \/ __|/ _ \| | | | '__/ __/ _ \/ __|
  // |  _ <  __/\__ \ (_) | |_| | | | (_|  __/\__ \
  // |_| \_\___||___/\___/ \__,_|_|  \___\___||___/
  //
  /////////////////////////////////////////////////////

  public function getAllReserveResources()
  {
    $reserve = [];
    foreach (RESOURCES as $res) {
      $reserve[$res] = 0;
    }

    foreach (Meeples::getInReserve($this->id) as $meeple) {
      if (in_array($meeple['type'], RESOURCES)) {
        $reserve[$meeple['type']]++;
      }
    }

    return $reserve;
  }

  public function getReserveResource($type = null)
  {
    return Meeples::getInReserve($this->id, $type);
  }

  public function countReserveResource($type)
  {
    return $this->getReserveResource($type)->count();
  }

  public function createResourceInReserve($type, $nbr = 1)
  {
    return Meeples::createResourceInReserve($this->id, $type, $nbr);
  }

  public function useResource($resource, $amount)
  {
    return Meeples::useResource($this->id, $resource, $amount);
  }

  public function payResourceTo($pId, $resource, $amount)
  {
    return Meeples::payResourceTo($this->id, $resource, $amount, $pId);
  }

  //////////////////////////////////////////////////////
  //  _____             _
  // | ____|_ __   __ _(_)_ __   ___  ___ _ __ ___
  // |  _| | '_ \ / _` | | '_ \ / _ \/ _ \ '__/ __|
  // | |___| | | | (_| | | | | |  __/  __/ |  \__ \
  // |_____|_| |_|\__, |_|_| |_|\___|\___|_|  |___/
  //             |___/
  //////////////////////////////////////////////////////

  public function getAvailableEngineers()
  {
    return $this->getReserveResource([ENGINEER, ARCHITECT]);
  }

  public function countAvailableEngineers()
  {
    return $this->getAvailableEngineers()->count();
  }

  public function hasAvailableEngineer()
  {
    return $this->countAvailableEngineers() > 0;
  }

  public function placeEngineer($spaceId, $nEngineers)
  {
    $engineerIds = array_slice($this->getAvailableEngineers()->getIds(), 0, $nEngineers);
    foreach ($engineerIds as $i => $id) {
      Meeples::move($id, $spaceId, $i);
    }
    return Meeples::getMany($engineerIds);
  }

  // Wheel
  public function getWheel()
  {
    $wheel = [];
    foreach (wheelSlots as $slot) {
      $wheel[$slot] = [
        'resources' => Meeples::getFilteredQuery($this->id, 'wheel_' . $slot, null)->get(),
        'tile' => TechnologyTiles::getFilteredQuery($this->id, 'wheel_' . $slot, null)->get(),
        'current' => $this->slot == $slot,
      ];
    }
    return $wheel;
  }

  public function rotateWheel()
  {
    $this->setSlot(($this->slot + 1) % 6);

    $mIds = Meeples::getFilteredQuery($this->id, 'wheel_' . $this->slot, null)
      ->get()
      ->getIds();
    if (!empty($mIds)) {
      Meeples::move($mIds, 'reserve');
    }

    $tId = TechnologyTiles::getFilteredQuery($this->id, 'wheel_' . $this->slot, null)
      ->get()
      ->getIds();
    if (!empty($tId)) {
      TechnologyTiles::move($tId, 'reserve');
    }

    Notifications::rotateWheel($this, 1);

    Notifications::recoverResources(
      $this,
      empty($mIds) ? [] : Meeples::getMany($mIds)->toArray(),
      empty($tId) ? [] : TechnologyTiles::getMany($tId)->toArray()
    );
    return;
  }

  public function placeOnWheel($type, $n)
  {
    return Meeples::moveResource($this->id, $type, $n, 'wheel_' . $this->slot);
  }

  public function canConstruct($type)
  {
    if (Meeples::getFilteredQuery($this->id, 'company', [$type])->count() == 0) {
      return false;
    }

    if (TechnologyTiles::getFilteredQuery($this->id, 'company', [$type, JOKER])->count() === 0) {
      return false;
    }
    return true;
  }

  public function incEnergy($n)
  {
    parent::incEnergy($n);
    $scoreToken = Meeples::getFilteredQuery($this->id, null, [SCORE])
      ->get()
      ->first();

    Meeples::move($scoreToken['id'], 'energy-track-' . $this->energy);
    return $scoreToken['id'];
  }

  // Contracts
  public function getContracts($resolved = null)
  {
    $q = Contracts::getSelectQuery()->where('contract_location', 'hand_' . $this->id);
    if ($resolved === true) {
      $q = $q->where('contract_state', 1);
    } elseif ($resolved === false) {
      $q = $q->where('contract_state', 0);
    }
    return $q->get();
  }

  public function getContractReduction()
  {
    if (is_null($this->officer)) {
      return 0;
    }
    return $this->officer->getContractReduction();
  }
}
