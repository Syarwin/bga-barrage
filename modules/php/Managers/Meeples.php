<?php
namespace BRG\Managers;
use BRG\Core\Stats;
use BRG\Helpers\UserException;

/* Class to manage all the meeples for Barrage */

class Meeples extends \BRG\Helpers\Pieces
{
  protected static $table = 'meeples';
  protected static $prefix = 'meeple_';
  protected static $customFields = ['type', 'company_id'];

  protected static function cast($meeple)
  {
    return [
      'id' => (int) $meeple['id'],
      'location' => $meeple['location'],
      'state' => $meeple['state'],
      'type' => $meeple['type'],
      'cId' => $meeple['company_id'],
    ];
  }

  public static function getUiData()
  {
    return self::getSelectQuery()
      ->get()
      ->toArray();
  }

  /* Creation of various meeples */
  public static function setupCompanies($companies)
  {
    $meeples = [];
    foreach ($companies as $cId => $company) {
      $meeples[] = ['type' => \ENGINEER, 'company_id' => $cId, 'location' => 'reserve', 'nbr' => 12];
      $meeples[] = ['type' => \CREDIT, 'company_id' => $cId, 'location' => 'reserve', 'nbr' => 6];
      $meeples[] = ['type' => \EXCAVATOR, 'company_id' => $cId, 'location' => 'reserve', 'nbr' => 6];
      $meeples[] = ['type' => \MIXER, 'company_id' => $cId, 'location' => 'reserve', 'nbr' => 4];

      // Structures
      for ($i = 0; $i < 5; $i++) {
        $meeples[] = ['type' => BASE, 'company_id' => $cId, 'location' => 'company', 'state' => $i];
        $meeples[] = ['type' => ELEVATION, 'company_id' => $cId, 'location' => 'company', 'state' => $i];
        $meeples[] = ['type' => CONDUIT, 'company_id' => $cId, 'location' => 'company', 'state' => $i];
        if ($i < 4) {
          $meeples[] = ['type' => POWERHOUSE, 'company_id' => $cId, 'location' => 'company', 'state' => $i];
        }
        // TODO : expansion : create buildings
      }
    }

    return self::getMany(self::create($meeples));
  }

  /**
   * Generic base query
   */
  public function getFilteredQuery($cId, $location, $type)
  {
    $query = self::getSelectQuery();
    if ($cId != null) {
      $query = $query->where('company_id', $cId);
    }
    if ($location != null) {
      $query = $query->where('meeple_location', strpos($location, '%') === false ? '=' : 'LIKE', $location);
    }
    if ($type != null) {
      if (is_array($type)) {
        $query = $query->whereIn('type', $type);
      } else {
        $query = $query->where('type', strpos($type, '%') === false ? '=' : 'LIKE', $type);
      }
    }
    return $query;
  }

  /**
   * Get meeples on a action space
   */
  public function getOnSpace($sId, $type = null, $cId = null)
  {
    return self::getFilteredQuery($cId, $sId, $type)->get();
  }

  /**
   * Get meeples in reserve
   */
  public function getInReserve($cId, $type = null)
  {
    return self::getFilteredQuery($cId, 'reserve', $type)->get();
  }

  /*************************** Resource management ***********************/
  public function useResource($cId, $resourceType, $amount)
  {
    $deleted = [];
    if ($amount == 0) {
      return [];
    }

    $resource = self::getInReserve($cId, $resourceType);
    if ($resource->count() < $amount) {
      throw new UserException(sprintf(clienttranslate('You do not have enough %s'), $resourceType));
    }

    foreach ($resource as $id => $res) {
      $deleted[] = $res;
      self::DB()->delete($id);
      $amount--;
      if ($amount == 0) {
        break;
      }
    }

    return $deleted;
  }

  public function payResourceTo($companyId, $resourceType, $amount, $otherCompany)
  {
    $moved = [];
    if ($amount == 0) {
      return [];
    }

    // $resource = self::getReserveResource($player_id, $resourceType);
    $resource = self::getFilteredQuery($companyId, 'reserve', [$resourceType])->get();

    if (count($resource) < $amount) {
      throw new UserException(sprintf(clienttranslate('You do not have enough %s'), $resourceType));
    }

    foreach ($resource as $id => $res) {
      self::DB()->update(
        [
          'company_id' => $otherCompany,
          'meeple_location' => 'reserve',
        ],
        $id
      );
      $res['cId'] = $otherCompany;
      $moved[] = $res;
      // self::DB()->delete($id);
      $amount--;
      if ($amount == 0) {
        break;
      }
    }
    return $moved;
  }

  public function createResourceInLocation($type, $location, $cId, $nbr = 1, $state = null)
  {
    $meeples = [
      [
        'type' => $type,
        'company_id' => $cId,
        'location' => $location,
        'nbr' => $nbr,
        'state' => $state,
      ],
    ];

    $ids = self::create($meeples);
    return self::getMany($ids);
  }

  public function createResourceInReserve($cId, $type, $nbr = 1)
  {
    return self::createResourceInLocation($type, 'reserve', $cId, $nbr);
  }

  //   ___  _     ____
  //  / _ \| |   |  _ \
  // | | | | |   | | | |
  // | |_| | |___| |_| |
  //  \___/|_____|____/
  //

  /**************************** Animals *****************************************/
  public function getAnimals($pId, $location = null)
  {
    return self::getFilteredQuery($pId, $location, [SHEEP, PIG, CATTLE])->get();
  }

  public function countAnimalsInZoneLocation($pId, $location = null)
  {
    return self::getFilteredQuery($pId, 'board', [SHEEP, PIG, CATTLE])
      ->where('x', $location['x'])
      ->where('y', $location['y'])
      ->count();
  }

  public function countAnimalsInZoneCard($pId, $location = null)
  {
    return self::getFilteredQuery($pId, $location['card_id'], [SHEEP, PIG, CATTLE])->count();
  }

  /**************************** Field *****************************************/
  public function getFields($pId)
  {
    return self::getFilteredQuery($pId, 'board', 'field')->get();
  }

  /**************************** Rooms *****************************************/
  protected function getRoomsQ($pId)
  {
    return self::getFilteredQuery($pId, 'board', 'room%');
  }

  public function getRooms($pId)
  {
    return self::getRoomsQ($pId)->get();
  }

  // countRooms
  public function countRooms($pId)
  {
    return self::getRoomsQ($pId)->count();
  }

  /**
   *
   * Provides the type of room constructed
   * @param number $player_id
   * @return string rommType (roomWood, roomClay, roomStone)
   */
  public function getRoomType($pId)
  {
    $roomsType = array_unique(
      self::getRooms($pId)
        ->map(function ($token) {
          return $token['type'];
        })
        ->toArray()
    );

    if (count($roomsType) != 1) {
      throw new \feException('multiple Room type, should not happen');
    }
    return $roomsType[0];
  }

  public function createResourceOnCard($type, $location, $nbr = 1, $state = null)
  {
    return self::createResourceInLocation($type, $location, 0, null, null, $nbr, $state);
  }

  public static function getOnCardQ($cId, $pId = null)
  {
    return self::getFilteredQuery($pId, $cId, null);
  }

  public function getResourcesOnCard($cId, $pId = null)
  {
    return self::getOnCardQ($cId, $pId)
      ->where('type', '<>', 'farmer')
      ->get();
  }

  public function collectResourcesOnCard($player, $cId, $pId = null)
  {
    // collect all resources on the card
    $resources = self::getResourcesOnCard($cId, $pId);
    foreach ($resources as $id => &$res) {
      self::DB()->update(
        [
          'player_id' => $player->getId(),
          'meeple_location' => 'reserve',
        ],
        $id
      );

      // Update for possible upcoming notifications
      $res['location'] = 'reserve';
      $res['pId'] = $player->getId();
    }

    return $resources->toArray();
  }

  public function receiveResource($player, &$meeple)
  {
    self::DB()->update(
      [
        'player_id' => $player->getId(),
        'meeple_location' => 'reserve',
      ],
      $meeple['id']
    );
    $meeple = self::get($meeple['id']);
  }

  /**
   * Return seeds on fields
   */
  public function getGrowingCrops($pId, $fieldCards = [])
  {
    $type = [VEGETABLE, GRAIN, STONE, WOOD];
    $locations = array_merge($fieldCards, ['board']);
    return self::getSelectQuery()
      ->wherePlayer($pId)
      ->whereIn('type', $type)
      ->whereIn('meeple_location', $locations)
      ->get();
  }

  /************************ Utility functions **********************/

  /**
   * Check if cell is adjacent
   * @param $x X coordinate of the new block
   * @param $y Y coordinate of the new block
   * @param $posX X coordinate existing block
   * @param $posY Y coordinate existing block
   * @return true if adjacent
   *
   **/
  public function isAdjacent($x, $y, $posX, $posY)
  {
    if (abs($x - $posX) == 1 && abs($y - $poxY) == 0) {
      return true;
    } elseif (abs($x - $posX) == 0 && abs($y - $posY) == 1) {
      return true;
    }

    return false;
  }

  public function getReserveResource($pId, $type = null)
  {
    $query = self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('meeple_location', 'reserve');

    if ($type != null) {
      $query = $query->where('type', $type);
    }
    return $query->get();
  }

  public function countReserveResource($pId, $type = null)
  {
    return self::getReserveResource($pId, $type)->count();
  }

  public function countAllResource($pId, $type)
  {
    $query = self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('type', $type);

    return $query->get()->count();
  }

  public function updateMaxima()
  {
    $types = [WOOD, CLAY, REED, STONE, GRAIN, VEGETABLE, SHEEP, PIG, CATTLE];
    foreach ($types as $type) {
      $name = 'get' . ucfirst($type) . 'Max';
      $v = Stats::$name();
      $c = self::getFilteredQuery(null, null, $type)->count();
      if ($c > $v) {
        $name = 'set' . ucfirst($type) . 'Max';
        Stats::$name($c);
      }
    }
  }
}
