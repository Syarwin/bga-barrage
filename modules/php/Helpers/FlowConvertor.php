<?php
namespace BRG\Helpers;

// Allow to use a short flow description syntax
abstract class FlowConvertor
{
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
