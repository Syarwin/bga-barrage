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
    $data['descs'] = $this->computeDescs();
    $data['cost'] = $this->cost;
    return $data;
  }

  public function pick($company)
  {
    $this->setLocation('hand_' . $company->getId());
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

  private function computeDescs()
  {
    $mapping = [
      CREDIT => clienttranslate('Gain ${n} Credits.'),
      EXCAVATOR => clienttranslate('Gain ${n} Excavator(s).'),
      MIXER => clienttranslate('Gain ${n} Mixer(s).'),
      ROTATE_WHEEL => clienttranslate('Rotate your Construction Wheel by ${n} segments.'),
      VP => clienttranslate('Gain ${n} victory points.'),
      PLACE_DROPLET => clienttranslate(
        'Place ${n} Water Drop(s) on Headstream tile(s) of your choice. These Water Drops will flow during the Water Flow Phase.'
      ),
      FLOW_DROPLET => clienttranslate(
        'Place ${n} Water Drop(s) on Headstream tile(s) of your choice. These Water Drops flow immediately.'
      ),
      ANY_MACHINE => clienttranslate('Gain ${n} Machineries of your choice.'),
      ENERGY => clienttranslate(
        'Move your Energy marker on the Energy Track by ${n} steps. You cannot use this amount of Energy Units to fulfill Contracts'
      ),
      CONDUIT => clienttranslate(
        'Build a Conduit with a production value of ${n} (or less). You don\'t need to place Engineers, to insert the Technology tile or the Machineries.'
      ),
      POWERHOUSE => clienttranslate('Place one of your Powerhouse in a free building space on the Map.'),
      ELEVATION => clienttranslate('Place one of your Elevation over one of your Dams.'),
      BASE => clienttranslate('Place one of your Bases in a free building space on the Map.'),
    ];

    $descs = [];
    foreach ($this->reward as $t => $n) {
      $desc = '';
      if (array_key_exists($t, $mapping)) {
        if (is_array($n)) {
          $desc = $mapping[$t];
        } else {
          $desc = [
            'log' => $mapping[$t],
            'args' => [
              'n' => $n,
            ],
          ];
        }
      }

      $descs[] = $desc;
    }

    return $descs;
  }

  public function fulfill($company)
  {
    // move contract to resolved
    $this->setLocation('fulfilled_' . $company->getId());

    // return flow of reward
    return $this->computeRewardFlow();
  }

  private function computeRewardFlow()
  {
    $mapping = [
      CREDIT => [['action' => GAIN, 'args' => [CREDIT]]],
      EXCAVATOR => [['action' => GAIN, 'args' => [EXCAVATOR]]],
      MIXER => [['action' => GAIN, 'args' => [MIXER]]],
      ROTATE_WHEEL => [['action' => \ROTATE_WHEEL]],
      VP => [['action' => GAIN, 'args' => [VP]]],
      PLACE_DROPLET => [['action' => PLACE_DROPLET, 'args' => ['flow' => false]]],
      FLOW_DROPLET => [['action' => PLACE_DROPLET, 'args' => ['flow' => true]]],
      ANY_MACHINE => [['type' => NODE_XOR, 'what' => ['action' => GAIN, 'args' => [EXCAVATOR, MIXER]]]],
      ENERGY => [['action' => GAIN, 'args' => [ENERGY]]],
      CONDUIT => [['action' => PLACE_STRUCTURE, 'args' => ['type' => CONDUIT]]],
      POWERHOUSE => [['action' => PLACE_STRUCTURE, 'args' => ['type' => POWERHOUSE]]],
      ELEVATION => [['action' => PLACE_STRUCTURE, 'args' => ['type' => ELEVATION]]],
      BASE => [['action' => PLACE_STRUCTURE, 'args' => ['type' => BASE]]],
    ];

    $flows = ['type' => NODE_SEQ, 'childs' => []];
    $gainFlow = null;

    foreach ($this->reward as $t => $n) {
      foreach ($mapping[$t] ?? null as $rFlow) {
        // if gain node, we will aggregate all gain
        if (isset($rFlow['action']) && $rFlow['action'] == GAIN) {
          if (is_null($gainFlow)) {
            $gainFlow = ['action' => GAIN, 'args' => []];
          }
          // foreach resource type of the node
          foreach ($rFlow['args'] ?? null as $resource) {
            if (!isset($gainFlow['args'][$resource])) {
              $gainFlow['args'][$resource] = 0;
            }
            $gainFlow['args'][$resource] += $n;
          }
          // throw new \feException(print_r($gainFlow));
        }
        // if XOR we know it's to gain EXCAVATOR or MIXER
        elseif (isset($rFlow['type']) && $rFlow['type'] == NODE_XOR) {
          $node = $rFlow;
          for ($i = 0; $i <= $n; $i++) {
            $node['childs'][] = ['action' => GAIN, 'args' => [EXCAVATOR => $i, MIXER => $n - $i]];
          }
          $flows['childs'][] = $node;
        } elseif (is_array($n)) {
          $rFlow['args']['constraints'] = $n;
          $flows['childs'][] = $rFlow;
        } else {
          $rFlow['args']['n'] = $n;
          $flows['childs'][] = $rFlow;
        }
      }
    }

    // filter out 0 resource value from the gain node
    if (!is_null($gainFlow)) {
      $gainFlow['args'] = array_filter($gainFlow['args'], function ($r) {
        return $r != 0;
      });
      \array_unshift($flows['childs'], $gainFlow);
    }
    // throw new \feException(print_r($flows));
    return $flows;
  }
}
