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

    return $powerhouses;
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
}
