<?php
namespace BRG\Models;
use BRG\Core\Stats;
use BRG\Core\Preferences;

/*
 * Contract: all utility functions concerning a contract
 */

class Contract extends \BRG\Helpers\DB_Model
{
  protected $table = 'contracts';
  protected $primary = 'contract_id';
  protected $attributes = [
    'id' => ['contract_id', 'int'],
    'location' => ['contract_location', 'str'],
    'type' => ['type', 'int'],
  ];
  protected $staticAttributes = ['cost', 'reward'];

  public function __construct($row, $datas)
  {
    parent::__construct($row);
    $this->cost = $datas['cost'];
    $this->reward = $datas['reward'];
  }

  public function jsonSerialize()
  {
    $data = parent::jsonSerialize();
    $data['icons'] = $this->computeIcons();
    $data['cost'] = $this->cost;
    return $data;
  }

  private function computeIcons()
  {
    $mapping = [
      CREDIT => 'CREDIT',
      EXCAVATOR => 'EXCAVATOR_ICON',
      MIXER => 'MIXER_ICON',
      ROTATE_WHEEL => 'ROTATE',
      VP => 'VP',
      PLACE_DROPLET => 'WATER',
      FLOW_DROPLET => 'WATER_DOWN',
      ANY_MACHINE => 'ANY_MACHINE',
      ENERGY => 'ENERGY',
      CONDUIT => 'CONDUIT_X',
      POWERHOUSE => 'POWERHOUSE',
      ELEVATION => 'ELEVATION',
      BASE => 'BASE',
    ];

    $icons = [];
    foreach ($this->reward as $t => $n) {
      $icon = '<CREDIT>';
      if (array_key_exists($t, $mapping)) {
        if (is_array($n)) {
          $icon = '<' . $mapping[$t] . '_' . strtoupper(implode('_', $n)) . '>';
        } else {
          $icon = '<' . $mapping[$t] . ":$n>";
        }
      }

      $icons[] = $icon;
    }

    return $icons;
  }
}
