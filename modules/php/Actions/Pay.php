<?php
namespace BRG\Actions;
use BRG\Core\Notifications;
use BRG\Core\Engine;
use BRG\Core\Game;
use BRG\Managers\companys;
use BRG\Managers\Companies;
use BRG\Managers\Meeples;
use BRG\Helpers\Utils;

class Pay extends \BRG\Models\Action
{
  public function getState()
  {
    return ST_PAY;
  }

  public function getDescription($ignoreResources = false)
  {
    $args = $this->argsPay(null, true);
    $combinations = $args['combinations'];

    // one combination => automatic allocation
    if (count($args['combinations']) == 1) {
      $cost = reset($args['combinations']);
      return [
        'log' => clienttranslate('Pay ${resources_desc}'),
        'args' => [
          'resources_desc' => Utils::resourcesToStr($cost),
        ],
      ];
    } else {
      return clienttranslate('Pay cost');
    }
  }

  public function isDoable($company, $ignoreResources = false)
  {
    $args = $this->argsPay($company);
    return $ignoreResources || !empty($args['combinations']);
  }

  public function isAutomatic($company = null)
  {
    $args = $this->argsPay();
    return count($args['combinations']) <= 1;
  }

  /**
   * Allow for args as $ctx instead of a tree node => useful for harvest
   */
  public function getCtxArgs()
  {
    return $this->ctx == null ? null : (is_array($this->ctx) ? $this->ctx : $this->ctx->getArgs());
  }

  public function argsPay($company = null, $ignoreResources = false)
  {
    $company = $company ?? Companies::getActive();
    $args = $this->getCtxArgs();
    $nb = $args['nb'] ?? 0;
    $combinations = self::computeAllBuyableCombinations($company, $args['costs'], $nb, $ignoreResources);
    $combinations = self::keepOnlyOptimals($combinations);

    $cardNames = [];
    foreach ($combinations as &$combination) {
      unset($combination['nb']);
    }

    return [
      'i18n' => ['source'],
      'nb' => $nb,
      'source' => $args['source'] ?? '',
      'combinations' => $combinations,
      'cardNames' => $cardNames,
      'descSuffix' => count($combinations) > 1 ? '' : 'auto',
    ];
  }

  public function stPay()
  {
    $args = $this->argsPay();

    // one combination => automatic allocation
    if (count($args['combinations']) == 1) {
      $cost = reset($args['combinations']);
      $this->actPay($cost, true);
    } elseif (empty($args['combinations'])) {
      throw new \BgaVisibleSystemException('No option to pay');
    }
  }

  public function actPay($cost, $isAuto = false)
  {
    // Sanity checks
    self::checkAction('actPay', $isAuto);
    $args = $this->argsPay();
    if (!in_array($cost, $args['combinations'])) {
      throw new \BgaVisibleSystemException('Cost not authorized');
    }

    $company = Companies::getActive();

    if (isset($this->getCtxArgs()['to'])) {
      // Payment to a player
      $moved = [];
      foreach ($cost as $resource => $amount) {
        if ($resource == 'sources') {
          continue;
        }
        $moved = array_merge($moved, $company->payResourceTo($this->getCtxArgs()['to'], $resource, $amount));
      }
      if (!empty($moved)) {
        Notifications::payResourcesTo(
          $company,
          $moved,
          $args['source'],
          $cost['sources'] ?? [],
          $args['cardNames'],
          Companies::get($this->getCtxArgs()['to'])
        );
      }
    } elseif (($this->getCtxArgs()['target'] ?? null) == 'wheel') {
      // Moving resources to wheel
      // Delete meeples and notify
      $deleted = [];
      $moved = [];
      foreach ($cost as $resource => $amount) {
        if ($resource == 'sources') {
          continue;
        }
        if (in_array($resource, MACHINERIES)) {
          $moved = array_merge($moved, $company->placeOnWheel($resource, $amount));
        } else {
          $deleted = array_merge($deleted, $company->useResource($resource, $amount));
        }
      }

      // Move tech tile
      $tile = $company->placeTileOnWheel($this->getCtxArgs()['tileId']);

      Notifications::payResourcesToWheel($company, $tile, $deleted, $moved, $args['source'], $cost['sources'] ?? []);
    } else {
      // "Normal" payment with ressources
      // Delete meeples and notify
      $deleted = [];
      foreach ($cost as $resource => $amount) {
        if ($resource == 'sources') {
          continue;
        }
        $deleted = array_merge($deleted, $company->useResource($resource, $amount));
      }
      if (!empty($deleted)) {
        Notifications::payResources($company, $deleted, $args['source'], $cost['sources'] ?? [], $args['cardNames']);
      }
    }

    // Resolve the node
    $this->resolveAction(['cost' => $cost]);
  }

  /***************************************
   ***************************************
   ********* STATIC COMPUTATIONS *********
   ***************************************
   **************************************/
  /*
   * FORMAT DOCUMENTATION
   *
   * $costs : [
   *    'fee' => [ 'resourceType' => amount]  -> this need to be paid no matter how many units we choose
   *    'fees' => [ fee1, fee2, ...], -> you need to pay at least one of the fees
   *    'trades' => [
   *        [
   *          'max' => maxAmount of time you can use this cost to buy units
   *          'nb' => amount of units we obtain by paying this
   *          'sources' => array of card ids implied by that cost
   *          'resourceType1' => amount,
   *           ....
   *        ]
   *    ]
   *    'cards' => [
   *        'type' => Type of card that can be exchanged
   *        'list' => authorized card for the exchange
   *    ],
   *    'bonuses' => [ -> list of cost reducers applied once to the TOTAL
   *        [
   *          'optional' => true/false,
   *          'resourceType' => amount,
   *          'sources' => array of card ids implied by that bonuses
   *          ...
   *        ]
   *     ],
   * ];
   *
   * $combination : [
   *   'nb' => amout of units we can obtain with this combination
   *   'resourceType1' => amount,
   *    ....
   * ]
   */
  public static function canPayFee($company, $costs)
  {
    return self::canBuy($company, $costs, 0);
  }

  public static function canBuy($company, $costs, $n = 1)
  {
    // Compute all buyable combinations
    // NOTICE : can't use maxBuyableAmount since there can be gaps in buyable amounts !!
    $combinations = self::computeAllBuyableCombinations($company, $costs);
    return \array_reduce(
      $combinations,
      function ($acc, $combination) use ($n) {
        return $acc || ($combination['nb'] ?? -1) == $n;
      },
      false
    );
  }

  public static function maxBuyableAmount($company, $costs)
  {
    // Compute all buyable combinations
    $combinations = self::computeAllBuyableCombinations($company, $costs);

    // Reduce to get the max
    return \array_reduce(
      $combinations,
      function ($acc, $combination) {
        return max($acc, $combination['nb'] ?? -1);
      },
      -1 // -1 instead of 0 to distinguish the case where company can afford the fee or not
    );
  }

  /**
   * Compute all the possibles combinations that a company can buy
   * @param $target (opt) : keep only combinations with nb == target
   */
  protected static function computeAllBuyableCombinations($company, $costs, $target = null, $ignoreResources = false)
  {
    $reserve = $company->getAllReserveResources();

    // Compute an artifical maxReserve to reduce computations by applying all potentially available bonuses
    $maxReserve = $reserve;
    $bonuses = $costs['bonuses'] ?? [];
    foreach ($bonuses as $bonus) {
      $choices = $bonus['choices'] ?? [$bonus]; // Handle bonuses with choices, eg "1 building resource of bonus"
      foreach ($choices as $choice) {
        foreach ($choice as $resource => $amount) {
          if ($resource != 'optional' && $resource != 'sources' && $amount < 0) {
            $maxReserve[$resource] += -$amount;
          }
        }
      }
    }

    // Start with an empty list of possible combinations
    $combinations = [];

    // First handle the fee
    $fees = $costs['fees'] ?? [$costs['fee'] ?? []];
    foreach ($fees as $baseCost) {
      $baseCost['nb'] = 0;
      self::pushAux($baseCost, $combinations, $maxReserve, false, $ignoreResources);
    }

    // Then loop over all possible trades
    $trades = $costs['trades'] ?? [];
    foreach ($trades as $trade) {
      $n = 1; // The number of time we want to use the trade
      $max = $trade['max'] ?? 15; // Just to ensure we won't have an infinite loop
      unset($trade['max']);
      $trade['nb'] = $trade['nb'] ?? 1;

      $previousCombinations = $combinations;
      do {
        $newCombinationPushed = false;
        foreach ($previousCombinations as $combination) {
          self::addCostAux($combination, $trade, $n);
          if ($target != null && $combination['nb'] > $target) {
            continue;
          }

          $pushed = self::pushAux($combination, $combinations, $maxReserve, false, $ignoreResources);
          $newCombinationPushed = $newCombinationPushed || $pushed;
        }
      } while ($newCombinationPushed && $n++ < $max);
    }

    // If target specified, keep only the combinations matching target
    if ($target != null) {
      Utils::filter($combinations, function ($combination) use ($target) {
        return ($combination['nb'] ?? -1) == $target;
      });
    }

    // Then handle bonuses
    foreach ($bonuses as $bonus) {
      $oldCombinations = $combinations;

      // Check if bonus is optional or not
      $optional = $bonus['optional'] ?? false;
      unset($bonus['optional']);
      $combinations = $optional ? $combinations : [];

      foreach ($oldCombinations as $comb) {
        $choices = $bonus['choices'] ?? [$bonus]; // Handle bonuses with choices, eg "1 building resource of bonus"
        foreach ($choices as $choice) {
          $combination = $comb;
          if (self::canApplyBonus($combination, $choice)) {
            self::addCostAux($combination, $choice);
          }
          self::pushAux($combination, $combinations, $maxReserve, false, $ignoreResources);
        }
      }
    }

    // Keep only combinations with >= 0 resource amount that fit inside RESERVE (and not maxReserve)
    $oldCombinations = $combinations;
    $combinations = [];
    foreach ($oldCombinations as $combination) {
      self::pushAux($combination, $combinations, $reserve, true, $ignoreResources);
    }

    return $combinations;
  }

  /**
   * Sum two assoc arrays of resources (multiplied by a coefficient)
   */
  protected function addCostAux(&$combination, $unitCost, $times = 1)
  {
    $combination['sources'] = array_unique(array_merge($combination['sources'] ?? [], $unitCost['sources'] ?? []));
    foreach ($unitCost as $resource => $cost) {
      if ($resource == 'sources') {
        continue;
      }

      $combination[$resource] = ($combination[$resource] ?? 0) + $times * $cost;
      if ($combination[$resource] == 0) {
        unset($combination[$resource]);
      }
    }
  }

  /**
   * Check if a bonus can be applied
   */
  protected function canApplyBonus($combination, $bonus)
  {
    foreach ($bonus as $resource => $amount) {
      if (
        $resource != 'optional' &&
        $resource != 'sources' &&
        $amount < 0 &&
        ($combination[$resource] ?? 0) + $amount < 0
      ) {
        return false;
      }
    }

    return true;
  }

  /**
   * Auxiliary push function that test if the combination is not already present
   *  AND if the resources are compatible with reserve
   */
  protected static function pushAux(
    $combination,
    &$combinations,
    $reserve,
    $checkNonNegative = false,
    $ignoreResources = false
  ) {
    if ($combinations === false) {
      $combinations = [];
    }

    // First look for duplicates
    foreach ($combinations as $c) {
      $d = $combination;
      unset($d['sources']);
      unset($c['sources']);
      $t1 = array_diff_assoc($c, $d);
      $t2 = array_diff_assoc($d, $c);
      if (empty($t1) && empty($t2)) {
        return false;
      }
    }

    // Then check reserve
    if (!$ignoreResources) {
      foreach ($combination as $res => $n) {
        if ($res == 'nb' || $res == 'sources') {
          continue;
        }

        $amountInReserve = $reserve[$res] ?? 0;
        if ($amountInReserve < $n) {
          return false;
        }

        if ($n < 0 && $checkNonNegative) {
          return false;
        }
      }
    }

    $combinations[] = $combination;
    return true;
  }

  /**
   * Auxiliary function that remove non optimal costs
   */
  protected static function keepOnlyOptimals($combinations)
  {
    $result = [];
    foreach ($combinations as $c) {
      if (self::isOptimal($c, $combinations)) {
        $result[] = $c;
      }
    }
    return $result;
  }

  protected static function isOptimal($c, $combinations)
  {
    foreach ($combinations as $c2) {
      if (self::isWorseThan($c, $c2)) {
        return false;
      }
    }
    return true;
  }

  protected static function isWorseThan($c1, $c2)
  {
    if ($c1['nb'] != $c2['nb'] || $c1 == $c2) {
      return false;
    }

    foreach ($c1 as $res => $n) {
      if ($res == 'sources') {
        continue;
      }
      if (isset($c2[$res]) && $c2[$res] > $n) {
        return false;
      }
    }

    foreach ($c2 as $res => $n) {
      if ($res == 'sources') {
        continue;
      }
      if (!isset($c1[$res]) || $c1[$res] < $n) {
        return false;
      }
    }

    return true;
  }
}
