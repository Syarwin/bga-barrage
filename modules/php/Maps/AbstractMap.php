<?php
namespace BRG\Maps;
use BRG\Managers\Meeples;

abstract class AbstractMap
{
  abstract public function getId();
  abstract public function getHeadstreams();
  abstract public function getZones();
  abstract public function getRivers();
  abstract public function getExits();

  public function getZoneId($spaceId)
  {
    $prefix = explode('_', $spaceId)[0];
    return (int) substr($prefix, 1);
  }

  public function getConduits()
  {
    if (isset($this->_conduits)) {
      return $this->_conduits;
    }

    $conduits = [];
    $zones = $this->getZones();
    foreach ($zones as $zId => $zone) {
      foreach ($zone['conduits'] ?? [] as $cId => $conduit) {
        // Compute connected powerhouses
        foreach ($zones[$conduit['end']]['powerhouses'] ?? [] as $i => $cost) {
          $conduit['powerhouses'][] = 'P' . $conduit['end'] . '_' . $i;
        }

        $conduit['area'] = $zone['area'];
        $conduit['id'] = $cId;
        $conduits[$cId] = $conduit;
      }
    }

    $this->_conduits = $conduits;
    return $conduits;
  }

  public function getPowerhouses()
  {
    if (isset($this->_powerhouses)) {
      return $this->_powerhouses;
    }

    $powerhouses = [];
    foreach ($this->getZones() as $zId => $zone) {
      foreach ($zone['powerhouses'] ?? [] as $i => $cost) {
        $uId = 'P' . $zId . '_' . $i;
        $powerhouses[$uId] = [
          'id' => $uId,
          'zone' => $zId,
          'cost' => $cost,
          'area' => $zone['area'],
        ];
      }
    }
    foreach (self::getConduits() as $cId => $info) {
      foreach ($info['powerhouses'] as $pId) {
        $powerhouses[$pId]['conduits'][] = $cId;
      }
    }

    $this->_powerhouses = \array_reverse($powerhouses);
    return $this->_powerhouses;
  }

  public function getPowerhousesInZone($zoneId)
  {
    $powerhouses = [];
    foreach ($this->getZones()[$zoneId]['powerhouses'] ?? [] as $i => $cost) {
      $uId = 'P' . $zoneId . '_' . $i;
      $powerhouses[] = $uId;
    }
    return $powerhouses;
  }

  public function getBasins()
  {
    if (isset($this->_basins)) {
      return $this->_basins;
    }

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

    $this->_basins = $basins;
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
