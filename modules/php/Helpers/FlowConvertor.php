<?php
namespace BRG\Helpers;

// Allow to use a short flow description syntax
abstract class FlowConvertor
{
  public static function getVp($rewards)
  {
    $vp = 0;
    foreach ($rewards as $t => $n) {
      if ($t == VP) {
        $vp += $n;
      }
    }
    return $vp;
  }

  public static function computeRewardFlow($rewards, $source = null, $isAI = false)
  {
    if ($isAI) {
      return self::computeRewardFlowAutoma($rewards, $source);
    }

    $mapping = [
      CREDIT => [['action' => GAIN, 'args' => [CREDIT]]],
      EXCAVATOR => [['action' => GAIN, 'args' => [EXCAVATOR]]],
      MIXER => [['action' => GAIN, 'args' => [MIXER]]],
      ROTATE_WHEEL => [['action' => \ROTATE_WHEEL]],
      VP => [['action' => GAIN, 'args' => [VP]]],
      PLACE_DROPLET => [['action' => PLACE_DROPLET, 'optional' => true, 'args' => ['flows' => false]]],
      FLOW_DROPLET => [['action' => PLACE_DROPLET, 'optional' => true, 'args' => ['flows' => true]]],
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
        // Very specific case for ext works granting two elevations
        elseif ($t == ELEVATION && $n > 1) {
          $rFlow['source'] = $source;
          for ($i = 0; $i < $n; $i++) {
            $flows['childs'][] = $rFlow;
          }
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

  public static function computeRewardFlowAutoma($rewards, $source = null)
  {
    $actions = [];
    foreach ($rewards as $t => $n) {
      if (in_array($t, [CREDIT, VP])) {
        $actions[] = [
          'type' => GAIN_VP,
          'vp' => $n,
        ];
      } elseif (in_array($t, [PLACE_DROPLET, FLOW_DROPLET])) {
        $actions[] = [
          'type' => PLACE_DROPLET,
          'n' => 1,
          'flow' => $t == FLOW_DROPLET,
        ];
      } elseif (in_array($t, [EXCAVATOR, MIXER, ANY_MACHINE])) {
        $actions[] = [
          'type' => GAIN_MACHINE,
          'machines' => [$t => $n],
        ];
      } elseif ($t == ROTATE_WHEEL) {
        $actions[] = [
          'type' => \ROTATE_WHEEL,
          'n' => $n,
        ];
      } elseif ($t == ENERGY) {
        $actions[] = [
          'type' => ENERGY,
          'energy' => $n,
        ];
      } elseif (in_array($t, [BASE, ELEVATION, CONDUIT, POWERHOUSE])) {
        $action = [
          'type' => \PLACE_STRUCTURE,
          'structure' => $t,
        ];
        if (is_array($n)) {
          $action['constraints'] = $n;
        } else {
          $action['n'] = $n; // Useful for conduit
        }

        // Very specific case for external works granting two elevations
        if ($t == ELEVATION && $n > 1) {
          for ($i = 0; $i < $n; $i++) {
            $actions[] = $action;
          }
        } else {
          $actions[] = $action;
        }
      }
    }

    return $actions;
  }
}
