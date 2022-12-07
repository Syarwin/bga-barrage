<?php
namespace BRG\Buildings;
use BRG\Managers\Companies;

/*
 * Building: all utility functions concerning a building tile
 */

class Building extends \BRG\Helpers\DB_Model
{
  protected $table = 'buildings';
  protected $primary = 'building_id';
  protected $attributes = [
    'id' => ['building_id', 'int'],
    'location' => 'building_location',
    'state' => ['building_state', 'int'],
    'type' => 'type',
  ];

  protected $staticAttributes = ['cost', 'flow', 'vp'];
  public function jsonSerialize()
  {
    $data = parent::jsonSerialize();
    $data['name'] = $this->getName();
    $data['cost'] = $this->getCost();
    $data['vp'] = $this->getvp();
    $data['icon'] = $this->getCentralIcon();
    return $data;
  }

  public function isAvailable()
  {
    return true;
  }

  public function getFlow()
  {
    return [];
  }

  public function getCentralIcon()
  {
    return [];
  }
}
