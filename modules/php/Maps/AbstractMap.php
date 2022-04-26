<?php
namespace BRG\Maps;

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
}
