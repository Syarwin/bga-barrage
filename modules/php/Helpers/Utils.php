<?php

namespace BRG\Helpers;

use BRG\Managers\PlayerCards;
use BRG\Managers\ActionCards;

abstract class Utils extends \APP_DbObject
{
  public static function filter(&$data, $filter)
  {
    $data = array_values(array_filter($data, $filter));
  }

  function shuffle(&$array)
  {
    $keys = array_keys($array);
    shuffle($keys);
    foreach ($keys as $key) {
      $new[$key] = $array[$key];
    }
    $array = $new;
    return true;
  }

  public static function rand($array, $n = 1)
  {
    $keys = array_rand($array, $n);
    if ($n == 1) {
      $keys = [$keys];
    }
    $entries = [];
    foreach ($keys as $key) {
      $entries[] = $array[$key];
    }
    shuffle($entries);
    return $entries;
  }

  public static function die($args = null)
  {
    throw new \BgaVisibleSystemException(json_encode($args));
  }

  /**
   * Reduce an array of meeples into a nice associative array $resource => $amount
   */
  public static function reduceResources($meeples)
  {
    $allResources = RESOURCES;
    $t = [];
    foreach ($allResources as $resource) {
      $t[$resource] = 0;
    }

    foreach ($meeples as $meeple) {
      if ($meeple['type'] == SCORE) {
        continue;
      }

      $t[$meeple['type']]++;
    }

    return $t;
  }

  /**
   * Return a string corresponding to an assoc array of resources
   */
  public static function resourcesToStr($resources)
  {
    $descs = [];
    foreach ($resources as $resource => $amount) {
      if (in_array($resource, ['sources', 'sourcesDesc', 'cId'])) {
        continue;
      }

      if ($amount == 0) {
        continue;
      }

      if (in_array($resource, [ENERGY])) {
        $descs[] = '<' . strtoupper($resource) . ':' . $amount . '>';
      } else {
        $descs[] = $amount . '<' . strtoupper($resource) . '>';
      }
    }
    return implode(',', $descs);
  }

  public static function formatCost($cost)
  {
    return [
      'trades' => [$cost],
    ];
  }

  public static function formatFee($cost)
  {
    return [
      'fees' => [$cost],
    ];
  }

  public static function addCost(&$costs, $cost, $source = null)
  {
    if ($source != null) {
      $cost['sources'] = [$source];
    }
    $costs['trades'][] = $cost;
  }

  public static function addFees(&$costs, $cost, $source = null)
  {
    if ($source != null) {
      $cost['sources'] = [$source];
    }
    $costs['fees'][] = $cost;
  }

  public static function addBonus(&$costs, $cost, $source = null, $optional = false)
  {
    if ($source != null) {
      $cost['sources'] = [$source];
    }
    if (!isset($cost['optional'])) {
      $cost['optional'] = $optional;
    }
    $costs['bonuses'][] = $cost;
  }

  public static function addBonusChoices(&$costs, $bonuses, $source = null, $optional = false)
  {
    if ($source != null) {
      foreach ($bonuses as &$cost) {
        $cost['sources'] = [$source];
      }
    }
    $costs['bonuses'][] = [
      'optional' => $optional,
      'choices' => $bonuses,
    ];
  }

  /**
   * Given an array [RESOURCE => [RESOURCE => amount, ...] ] , format as a proper exchange
   */
  public static function formatExchange($exchange, $source = '', $triggers = null, $flag = null)
  {
    $key = array_keys($exchange)[0];
    return [
      'source' => $source,
      'flag' => $flag,
      'triggers' => $triggers,
      'max' => $exchange['max'] ?? 9999,
      'from' => [
        $key => 1,
      ],
      'to' => $exchange[$key],
    ];
  }

  /**
   * Wrapper for getting action card : either use actionCards (for usual cases) or playerCards (for C104_Collector)
   */
  public static function getActionCard($id)
  {
    if (strpos($id, '_') === false) {
      return ActionCards::get($id);
    } else {
      return PlayerCards::get($id);
    }
  }

  public static function topological_sort($nodeids, $edges)
  {
    $L = $S = $nodes = [];
    foreach ($nodeids as $id) {
      $nodes[$id] = ['in' => [], 'out' => []];
      foreach ($edges as $e) {
        if ($id == $e[0]) {
          $nodes[$id]['out'][] = $e[1];
        }
        if ($id == $e[1]) {
          $nodes[$id]['in'][] = $e[0];
        }
      }
    }
    foreach ($nodes as $id => $n) {
      if (empty($n['in'])) {
        $S[] = $id;
      }
    }
    while (!empty($S)) {
      $L[] = $id = array_shift($S);
      foreach ($nodes[$id]['out'] as $m) {
        $nodes[$m]['in'] = array_diff($nodes[$m]['in'], [$id]);
        if (empty($nodes[$m]['in'])) {
          $S[] = $m;
        }
      }
      $nodes[$id]['out'] = [];
    }
    foreach ($nodes as $n) {
      if (!empty($n['in']) or !empty($n['out'])) {
        return null; // not sortable as graph is cyclic
      }
    }
    return $L;
  }
}
