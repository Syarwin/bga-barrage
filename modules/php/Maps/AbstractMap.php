<?php
namespace BRG\Maps;

use BRG\Managers\Meeples;
use BRG\Core\Notifications;

abstract class AbstractMap
{
  abstract public function getId();
  abstract public function getHeadstreams();
  abstract public function getZones();
  abstract public function getRivers();

  public function getConduits()
  {
    $conduits = [];
    foreach ($this->getZones() as $zone) {
      foreach ($zone['conduits'] ?? [] as $cId => $conduit) {
        $conduit['id'] = $cId;
        $conduits[$cId] = $conduit;
      }
    }

    return $conduits;
  }

  public function getPowerhouses()
  {
    $powerhouses = [];
    foreach ($this->getZones() as $zId => $zone) {
      foreach ($zone['powerhouses'] ?? [] as $i => $cost) {
        $uId = 'P' . $zId . '_' . $i;
        $powerhouses[$uId] = [
          'id' => $uId,
          'zone' => $zId,
          'cost' => $cost,
        ];
      }
    }

    return \array_reverse($powerhouses);
  }

  public function getBasins()
  {
    $basins = [];
    foreach ($this->getZones() as $zId => $zone) {
      foreach ($zone['basins'] ?? [] as $bId) {
        $basins[$bId] = [
          'id' => $bId,
          'zone' => $zId,
          'area' => $zone['area'],
          'cost' => strpos($bId, 'U') === false ? 0 : 3,
        ];
      }
    }

    return $basins;
  }

  public function getConstructSlots()
  {
    $slots = [];
    foreach ($this->getBasins() as $bId => $basin) {
      $basin['type'] = Meeples::getOnSpace($bId)->empty() ? BASE : ELEVATION;
      $slots[] = $basin;
    }

    foreach ($this->getPowerhouses() as $powerhouse) {
      $powerhouse['type'] = POWERHOUSE;
      $slots[] = $powerhouse;
    }

    foreach ($this->getConduits() as $conduit) {
      $conduit['type'] = CONDUIT;
      $slots[] = $conduit;
    }

    return $slots;
  }

  public function getBasinsByArea()
  {
    $basins = [
      MOUNTAIN => [],
      HILL => [],
      PLAIN => [],
    ];

    foreach (self::getBasins() as $basin) {
      $basins[$basin['area']][] = $basin;
    }

    return $basins;
  }

  /************* FLOW ************/

  public function flow($dropletId)
  {
    $droplet = Meeples::get($dropletId);

    if ($droplet == null) {
      throw new \BgaVisibleSystemException("Droplet doesn't exist. shouldn't happen");
    }

    $blocked = false;
    $rivers = $this->getRivers();
    do {
      // search for the next basin
      $original = $droplet;
      $basin = $rivers[$droplet['location']] ?? null;
      if (\is_null($basin)) {
        throw new \BgaVisibleSystemException('Unknown route for droplet. Should not happen');
      }

      // move the droplet
      $droplet['location'] = $basin;
      Notifications::moveDroplet($droplet, $original);

      // removeDroplet
      if ($basin == 'EXIT') {
        $blocked = true;
        Meeples::DB()->delete($droplet['id']);
        Notifications::silentDestroy([$droplet]);
      }
      // Droplet is blocked
      // TODO : handle company that can hold 4 droplet with 3 elevation or sthg like that
      elseif ($this->countDropletsInBasin($basin) < $this->countDamsInBasin($basin)) {
        Meeples::DB()->update(['meeple_location' => $basin], $droplet['id']);
        $blocked = true;
      }
    } while (!$blocked);
    return $droplet;
  }

  public function countDamsInBasin($basin)
  {
    return Meeples::getFilteredQuery(null, $basin, [BASE, \ELEVATION])->count();
  }

  public function countDropletsInBasin($basin)
  {
    return Meeples::getFilteredQuery(null, $basin, [DROPLET])->count();
  }

  /*************** PRODUCTION *******************/
  public function productionCapacity($company)
  {
    $capacity = [];
    // for each basin check that we have
    $conduits = $this->getConduits();
    foreach ($this->getZones() as $zoneId => $zone) {
      // check if there are some conduits
      $conduits = [];
      foreach ($zone['conduits'] ?? [] as $cId => $conduit) {
        $con = Meeples::getFilteredQuery(null, $cId, [CONDUIT])->get();
        if ($con->count() == 0) {
          continue;
        }
        $conduit['owner'] = $con->first()['cId'];
        if (Meeples::getFilteredQuery($company, 'P' . $conduit['end'] . '%', [POWERHOUSE])->count() == 0) {
          continue;
        }
        $conduits[$cId] = $conduit;
      }

      // not conduit => cannot produce in this zone
      if (empty($conduits)) {
        continue;
      }

      $droplets = 0;

      foreach ($conduits as $cId => $conduit) {
        foreach ($zone['basins'] ?? [] as $basin) {
          if (Meeples::getFilteredQuery(COMPANY_NEUTRAL, $basin, [BASE, \ELEVATION])->count() > 0) {
            $capacity[] = [
              'conduit' => $conduit,
              'conduitId' => $cId,
              'basin' => $basin,
              'droplets' => $this->countDropletsInBasin($basin),
            ];
          }
          if (Meeples::getFilteredQuery($company, $basin, [BASE, \ELEVATION])->count() > 0) {
            $capacity[] = [
              'conduit' => $conduit,
              'conduitId' => $cId,
              'basin' => $basin,
              'droplets' => $this->countDropletsInBasin($basin),
            ];
          }
        }
        // throw new \feException(print_r($capacity));
      }
    }

    $capacity = array_filter($capacity, function ($c) {
      return $c['droplets'] != 0;
    });

    $credits = Meeples::getFilteredQuery($company, 'reserve', [CREDIT])->count();
    foreach ($capacity as &$cap) {
      if ($cap['conduit']['owner'] != $company && $cap['droplets'] > $credits) {
        $cap['droplets'] = $credits;
      }
    }

    return $capacity;
  }
}
