<?php
namespace BRG\Models;
use BRG\Managers\Farmers;
use BRG\Managers\Actions;
use BRG\Managers\Meeples;
use BRG\Managers\Fences;
use BRG\Managers\PlayerCards;
use BRG\Managers\Contracts;
use BRG\Managers\Officers;
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
    'officerId' => ['xo', 'int'],
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
      'officer' => $this->officer,
      'scoreAux' => $this->scoreAux,
      'energy' => $this->energy,
      'wheelAngle' => $this->slot,
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

  public function isXO($xId)
  {
    return $this->officerId == $xId;
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

  public function incEnergy($n, $notif = true)
  {
    parent::incEnergy($n);
    $scoreToken = Meeples::getFilteredQuery($this->id, null, [SCORE])
      ->get()
      ->first();

    Meeples::move($scoreToken['id'], 'energy-track-' . $this->energy);
    if ($notif) {
      Notifications::moveTokens(Meeples::getMany($scoreToken['id']));
    }

    return $scoreToken['id'];
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

  ///////////////////////////////////////
  // __        ___               _
  // \ \      / / |__   ___  ___| |
  //  \ \ /\ / /| '_ \ / _ \/ _ \ |
  //   \ V  V / | | | |  __/  __/ |
  //    \_/\_/  |_| |_|\___|\___|_|
  //
  ///////////////////////////////////////

  public function placeOnWheel($type, $n)
  {
    return Meeples::moveResource($this->id, $type, $n, 'wheel', $this->slot);
  }

  public function placeTileOnWheel($tileId)
  {
    TechnologyTiles::move($tileId, 'wheel', $this->slot);
    return TechnologyTiles::get($tileId);
  }

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

    $mIds = Meeples::getFilteredQuery($this->id, 'wheel', $this->slot, null)
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

  ////////////////////////////////////////////////////
  //   ____                _                   _
  //  / ___|___  _ __  ___| |_ _ __ _   _  ___| |_
  // | |   / _ \| '_ \/ __| __| '__| | | |/ __| __|
  // | |__| (_) | | | \__ \ |_| |  | |_| | (__| |_
  //  \____\___/|_| |_|___/\__|_|   \__,_|\___|\__|
  //
  ////////////////////////////////////////////////////
  /*
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
*/

  public function getAvailableTechTiles($structure = null)
  {
    $tiles = TechnologyTiles::getFilteredQuery($this->id, 'company')->get();
    if (!is_null($structure)) {
      $tiles = $tiles->filter(function ($tile) use ($structure) {
        return $tile->canConstruct($structure);
      });
    }
    return $tiles;
  }

  protected $costMap = [
    BASE => ['type' => EXCAVATOR, MOUNTAIN => 5, HILL => 4, PLAIN => 3],
    ELEVATION => ['type' => MIXER, MOUNTAIN => 4, HILL => 3, PLAIN => 2],
    POWERHOUSE => ['type' => MIXER],
    CONDUIT => ['type' => EXCAVATOR],
  ];

  public function getConstructCost($slot, $tile)
  {
    $cost = $this->costMap[$slot['type']];
    $machine = $cost['type'];
    $n = 0;

    switch ($slot['type']) {
      case BASE:
      case ELEVATION:
        $n = $cost[$slot['area']];
        break;

      case POWERHOUSE:
        $n = 6 - Meeples::getFilteredQuery($this->id, 'company', POWERHOUSE)->count();
        break;

      case CONDUIT:
        $n = 2 * $slot['production'];
        break;
    }

    // TODO : handle tile modifier
    return [
      'nb' => $n,
      'costs' => Utils::formatCost([$machine => $n, 'nb' => $n]),
    ];
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

  public function earnIncome()
  {
    $revenueBoard = $this->getRevenueBoard();
    $flows = ['type' => NODE_SEQ, 'childs' => []];
    $gainFlow = [];
    foreach ([BASE, ELEVATION, CONDUIT] as $type) {
      $nb = Meeples::getFilteredQuery($this->id, null, $type)
        ->whereNotIn('meeple_location', ['company'])
        ->count();

      for ($i = 1; $i <= $nb; $i++) {
        if (!isset($revenueBoard[$type][$i])) {
          continue;
        }

        $tmpFlow = $revenueBoard[$type][$i];
        if (isset($tmpFlow['action']) && $tmpFlow['action'] == GAIN) {
          foreach ($tmpFlow['args'] as $resource => $amount) {
            if (!isset($gainFlow[$resource])) {
              $gainFlow[$resource] = $amount;
            } else {
              $gainFlow[$resource] += $amount;
            }
          }
        } else {
          $flows['childs'][] = $tmpFlow;
        }
      }
    }
    if (!empty($gainFlow)) {
      \array_unshift($flows['childs'], ['action' => GAIN, 'args' => $gainFlow]);
    }
    if (!empty($flows['childs'])) {
      return $flows;
    } else {
      return [];
    }
  }
}
