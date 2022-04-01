<?php
namespace BRG\Managers;
use agricola;
use BRG\Core\Stats;
use BRG\Helpers\UserException;

/* Class to manage all the meeples for Agricola */

class Meeples extends \BRG\Helpers\Pieces
{
  protected static $table = 'meeples';
  protected static $prefix = 'meeple_';
  protected static $customFields = ['type', 'player_id', 'x', 'y'];

  protected static function cast($meeple)
  {
    return [
      'id' => (int) $meeple['id'],
      'location' => $meeple['location'],
      'pId' => $meeple['player_id'],
      'type' => $meeple['type'],
      'x' => $meeple['x'],
      'y' => $meeple['y'],
      'state' => $meeple['state'],
    ];
  }

  public static $renovationCost = [
    'roomWood' => ['multiple' => CLAY, 'once' => REED, 'next' => 'roomClay'],
    'roomClay' => ['multiple' => STONE, 'once' => REED, 'next' => 'roomStone'],
  ];

  public static function getUiData()
  {
    return self::getSelectQuery()
      ->orderBy('meeple_state')
      ->orderBy('type') // Ensure fields are created before grain and vegetable that mights be on them
      ->get()
      ->toArray();
  }

  /* Creation of various meeples */
  public static function setupNewGame($players, $options)
  {
    $player_order = agricola::get()->getNextPlayerTable();

    // 1st player has 2 food
    // other players have 3 foods
    $meeples = [];
    if (count($players) > 1) {
      $meeples[] = ['type' => FOOD, 'player_id' => $player_order[0], 'location' => 'reserve', 'nbr' => 2];
    }
    $meeples[] = ['type' => 'firstPlayer', 'player_id' => $player_order[0], 'location' => 'reserve', 'nbr' => 1];
    foreach ($players as $player_id => $player) {
      if ($player_id !== $player_order[0]) {
        $meeples[] = ['type' => FOOD, 'player_id' => $player_id, 'location' => 'reserve', 'nbr' => 3];
      }

      // rooms
      $meeples[] = [
        'type' => 'roomWood',
        'player_id' => $player_id,
        'location' => 'board',
        'x' => 1,
        'y' => 3,
        'nbr' => 1,
      ];
      $meeples[] = [
        'type' => 'roomWood',
        'player_id' => $player_id,
        'location' => 'board',
        'x' => 1,
        'y' => 5,
        'nbr' => 1,
      ];

      // fence
      $meeples[] = ['type' => 'fence', 'player_id' => $player_id, 'location' => 'reserve', 'nbr' => 15];
      // stables
      $meeples[] = ['type' => 'stable', 'player_id' => $player_id, 'location' => 'reserve', 'nbr' => 4];
    }

    self::create($meeples);

    Farmers::setupNewGame($players, $options);
  }

  /**
   * move meeple token to coords
   * @param number $mId meeple id
   * @param varchar $location place on which we put the meeple
   * @param array $coord X & Y position
   **/
  public function moveToCoords($mId, $location, $coord = null)
  {
    $x = null;
    $y = null;

    if (is_array($coord) && isset($coord['x']) && isset($coord['y'])) {
      $x = $coord['x'];
      $y = $coord['y'];
    } elseif (is_array($coord) && count($coord) == 2) {
      $x = $coord[0];
      $y = $coord[1];
    } elseif (is_array($coord)) {
      $x = $coord[0];
    } elseif ($coord != null) {
      $x = $coord;
    }

    self::DB()->update(
      [
        'meeple_location' => $location,
        'x' => $x,
        'y' => $y,
      ],
      $mId
    );
  }

  /**
   * Generic base query
   */
  public function getFilteredQuery($pId, $location, $type)
  {
    $query = self::getSelectQuery()->wherePlayer($pId);
    if ($location != null) {
      $query = $query->where('meeple_location', $location);
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

  /*************************** Resource management ***********************/
  public function useResource($player_id, $resourceType, $amount)
  {
    $deleted = [];
    if ($amount == 0) {
      return [];
    }

    // $resource = self::getReserveResource($player_id, $resourceType);
    $resource = self::getResourceOfType($player_id, $resourceType);

    if (count($resource) < $amount) {
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

  public function payResourceTo($player_id, $resourceType, $amount, $otherPlayer)
  {
    $moved = [];
    if ($amount == 0) {
      return [];
    }

    // $resource = self::getReserveResource($player_id, $resourceType);
    $resource = self::getResourceOfType($player_id, $resourceType);

    if (count($resource) < $amount) {
      throw new UserException(sprintf(clienttranslate('You do not have enough %s'), $resourceType));
    }

    foreach ($resource as $id => $res) {
      self::DB()->update(
        [
          'player_id' => $otherPlayer,
          'meeple_location' => 'reserve',
        ],
        $id
      );
      $res['pId'] = $otherPlayer;
      $moved[] = $res;
      // self::DB()->delete($id);
      $amount--;
      if ($amount == 0) {
        break;
      }
    }
    return $moved;
  }

  public function createResourceInLocation($type, $location, $player_id, $x, $y, $nbr = 1, $state = null)
  {
    $meeples = [
      [
        'type' => $type,
        'player_id' => $player_id,
        'location' => $location,
        'x' => $x,
        'y' => $y,
        'nbr' => $nbr,
        'state' => $state,
      ],
    ];

    $ids = self::create($meeples);
    self::updateMaxima();
    return $ids;
  }

  public function createResourceOnCard($type, $location, $nbr = 1, $state = null)
  {
    return self::createResourceInLocation($type, $location, 0, null, null, $nbr, $state);
  }

  // Default function to create a resource in reserve
  public function createResourceInReserve($pId, $type, $nbr = 1)
  {
    return self::createResourceInLocation($type, 'reserve', $pId, null, null, $nbr);
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

  public function collectFirstPlayerToken($pId)
  {
    $tokenId = self::getSelectQuery()
      ->where('type', 'firstPlayer')
      ->getSingle()['id'];

    self::DB()->update(['player_id' => $pId], $tokenId);
    return $tokenId;
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

  public function getResourceOfType($pId, $type)
  {
    $query = self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('type', $type)
      ->where('meeple_location', 'NOT LIKE', 'turn_%')
      ->orderBy('meeple_location', 'DESC');

    return $query->get();

    /*
TODO : smart choice for animals
public function getNextSheep()
{
  // Try to find a sheep on the player board
  $zones = $this->getPlayer()->board()->getAnimalsDropZonesWithAnimals();

  // Sort rooms first, then stable, then pasture
  usort($zones, function ($a, $b) {
    $map = [
      'D148_special' => 3,
      'room' => 2,
      'stable' => 1,
      'pasture' => 0,
    ];
    return $map[$b['type']] - $map[$a['type']];
  });

  foreach ($zones as &$zone) {
    if ($zone[SHEEP] > 0) {
      foreach ($zone['meeples'] as $meeple) {
        if ($meeple['type'] == SHEEP) {
          return $meeple;
        }
      }
    }
  }

  return null;
}
*/
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
    foreach($types as $type){
      $name = "get".ucfirst($type).'Max';
      $v = Stats::$name();
      $c = self::getFilteredQuery(null, null, $type)->count();
      if($c > $v){
        $name = "set".ucfirst($type).'Max';
        Stats::$name($c);
      }
    }
  }
}
