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
      foreach ($company->getStartingResources() as $type => $nbr) {
        $meeples[] = [
          'type' => $type,
          'company_id' => $cId,
          'location' => 'reserve',
          'nbr' => $nbr,
        ];
      }

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

      $meeples[] = ['type' => SCORE, 'company_id' => $cId, 'location' => 'energy-track-0', 'nbr' => 1];
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
      if (is_array($cId)) {
        $ids = array_map(function ($c) {
          return is_int($c) ? $c : $c->getId();
        }, $cId);
        $query = $query->whereIn('company_id', $ids);
      } else {
        $cId = is_int($cId) ? $cId : $cId->getId();
        $query = $query->where('company_id', $cId);
      }
    }
    if ($location != null) {
      if (is_array($location)) {
        $query = $query->whereIn('meeple_location', $location);
      } else {
        $query = $query->where('meeple_location', strpos($location, '%') === false ? '=' : 'LIKE', $location);
      }
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

  /**
   * Get meeples on wheel
   */
  public function getOnWheel($cId, $slot)
  {
    return self::getFilteredQuery($cId, 'wheel', null)
      ->where('meeple_state', $slot)
      ->get();
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

  public function moveResource($companyId, $resourceType, $amount, $location, $state = 0)
  {
    $moved = [];
    if ($amount == 0) {
      return [];
    }

    $resource = self::getFilteredQuery($companyId, 'reserve', [$resourceType])
      ->limit($amount)
      ->get();

    if (count($resource) < $amount) {
      throw new UserException(sprintf(clienttranslate('You do not have enough %s'), $resourceType));
    }

    foreach ($resource as $id => $res) {
      self::DB()->update(
        [
          'meeple_location' => $location,
          'meeple_state' => $state,
        ],
        $id
      );
      $res['location'] = $location;
      $res['state'] = $state;
      $moved[] = $res;
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

  public function getTopOfType($type, $company, $location, $n = 1, $returnValueIfOnlyOneRow = true)
  {
    self::checkLocation($location);
    self::checkPosInt($n);
    return self::getSelectWhere(null, $location)
      ->where([['type', $type]])
      ->where([['company_id', $company]])
      ->orderBy(static::$prefix . 'state', 'DESC')
      ->limit($n)
      ->get($returnValueIfOnlyOneRow);
  }

  public function getEnergyTokens()
  {
    return self::getFilteredQuery(null, null, [SCORE])->get();
  }

  public function resetEnergyTokens()
  {
    $tokensIds = self::getEnergyTokens()->getIds();
    Meeples::move($tokensIds, 'energy-track-0');
    return self::getEnergyTokens();
  }
}
