<?php
namespace BRG\Helpers;

// Allow to use a short flow description syntax
abstract class FlowConvertor
{
  public static function computeIcons($rewards)
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
    foreach ($rewards as $t => $n) {
      $icon = '<CREDIT>';
      if (array_key_exists($t, $mapping)) {
        if (is_array($n)) {
          $icon = '<' . $mapping[$t] . '_' . strtoupper(implode('_', $n)) . '>';
        } else {
          $icon = '<' . $mapping[$t] . ":$n>";
        }
      } elseif ($t == PRODUCTION_BONUS) {
        $icon = '[+' . $n . ']';
      }

      $icons[] = $icon;
    }

    return $icons;
  }

  public static function computeDescs($rewards)
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
      PRODUCTION_BONUS => clienttranslate('Permanent bonus of +${n} on your productions.'),
    ];

    $descs = [];
    foreach ($rewards as $t => $n) {
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

  public static function computeRewardFlow($rewards, $source = null)
  {
    $mapping = [
      CREDIT => [['action' => GAIN, 'args' => [CREDIT]]],
      EXCAVATOR => [['action' => GAIN, 'args' => [EXCAVATOR]]],
      MIXER => [['action' => GAIN, 'args' => [MIXER]]],
      ROTATE_WHEEL => [['action' => \ROTATE_WHEEL]],
      VP => [['action' => GAIN, 'args' => [VP]]],
      PLACE_DROPLET => [['action' => PLACE_DROPLET, 'args' => ['flows' => false]]],
      FLOW_DROPLET => [['action' => PLACE_DROPLET, 'args' => ['flows' => true]]],
      ANY_MACHINE => [['type' => NODE_XOR]],
      ENERGY => [['action' => GAIN, 'args' => [ENERGY]]],
      CONDUIT => [['action' => PLACE_STRUCTURE, 'optional' => true, 'args' => ['type' => CONDUIT]]],
      POWERHOUSE => [['action' => PLACE_STRUCTURE, 'optional' => true, 'args' => ['type' => POWERHOUSE]]],
      ELEVATION => [['action' => PLACE_STRUCTURE, 'optional' => true, 'args' => ['type' => ELEVATION]]],
      BASE => [['action' => PLACE_STRUCTURE, 'optional' => true, 'args' => ['type' => BASE]]],
    ];

    $flows = ['type' => NODE_SEQ, 'childs' => []];
    $gainFlow = null;

    foreach ($rewards as $t => $n) {
      foreach ($mapping[$t] ?? null as $rFlow) {
        // if gain node, we will aggregate all gain
        if (isset($rFlow['action']) && $rFlow['action'] == GAIN) {
          if (is_null($gainFlow)) {
            $gainFlow = ['action' => GAIN, 'args' => [], 'source' => $source];
          }
          // foreach resource type of the node
          foreach ($rFlow['args'] ?? null as $resource) {
            if (!isset($gainFlow['args'][$resource])) {
              $gainFlow['args'][$resource] = 0;
            }
            $gainFlow['args'][$resource] += $n;
          }
        }
        // if XOR we know it's to gain EXCAVATOR or MIXER
        elseif (isset($rFlow['type']) && $rFlow['type'] == NODE_XOR) {
          for ($i = 0; $i <= $n; $i++) {
            $rFlow['childs'][] = ['action' => GAIN, 'args' => [EXCAVATOR => $i, MIXER => $n - $i, 'source' => $source]];
          }
          $flows['childs'][] = $rFlow;
        }
        // if $n is an array, it's the constraint for placement of a structure
        elseif (is_array($n)) {
          $rFlow['args']['constraints'] = $n;
          $rFlow['source'] = $source;
          $flows['childs'][] = $rFlow;
        }
        // Otherwise it's just a basic action
        else {
          $rFlow['args']['n'] = $n;
          $rFlow['source'] = $source;
          $flows['childs'][] = $rFlow;
        }
      }
    }

    // Add the gainFlow node
    if (!is_null($gainFlow)) {
      array_unshift($flows['childs'], $gainFlow);
    }

    return $flows;
  }
}
