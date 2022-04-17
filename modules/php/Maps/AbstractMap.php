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
          'zone' => $zId,
          'cost' => $cost,
        ];
      }
    }

    return $powerhouses;
  }
}
