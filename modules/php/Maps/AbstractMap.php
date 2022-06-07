<?php
namespace BRG\Maps;
use BRG\Managers\Meeples;

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

  public function getLocationsInZone($zId)
  {
    $zone = $this->getZones()[$zId];

    $locations = $zone['basins'] ?? [];
    $locations = array_merge($locations, array_keys($zone['conduits'] ?? []));
    for ($i = 0; $i < count($zone['powerhouses'] ?? []); $i++) {
      $locations[] = 'P' . $zId . '_' . $i;
    }
    return $locations;
  }

  public function getLocationsInArea($area)
  {
    $locations = [];
    foreach ($this->getZones() as $zId => $zone) {
      if ($zone['area'] == $area) {
        $locations = array_merge($locations, self::getLocationsInZone($zId));
      }
    }

    return $locations;
  }
}
