<?php
namespace BRG\Actions;
use BRG\Core\Notifications;
use BRG\Core\Engine;
use BRG\Core\Game;
use BRG\Managers\Players;
use BRG\Managers\PlayerCards;
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

    $harvestFlag = $this->getCtxArgs()['harvest'] ?? false;
    if ($this->isHarvest() && $harvestFlag) {
      return $this->getHarvestDescription();
    }

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

  public function isDoable($player, $ignoreResources = false)
  {
    $args = $this->argsPay($player);
    $harvestFlag = $this->getCtxArgs()['harvest'] ?? false;
    return $ignoreResources || ($this->isHarvest() && $harvestFlag) || !empty($args['combinations']);
  }

  public function isAutomatic($player = null)
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

  public function argsPay($player = null, $ignoreResources = false)
  {
    $player = $player ?? Players::getActive();
    $args = $this->getCtxArgs();
    $nb = $args['nb'] ?? 0;
    $combinations = self::computeAllBuyableCombinations($player, $args['costs'], $nb, $ignoreResources);
    $combinations = self::keepOnlyOptimals($combinations);

    // Remove NB and fetch card names for UI
    $cardNames = [];
    foreach ($combinations as &$combination) {
      unset($combination['nb']);
      if (isset($combination['card'])) {
        $cardNames[$combination['card']] = PlayerCards::get($combination['card'])->getName();
      }

      foreach ($combination['sources'] ?? [] as $cardId) {
        $cardNames[$cardId] = PlayerCards::get($cardId)->getName();
      }
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
    $harvestFlag = $this->getCtxArgs()['harvest'] ?? false;

    // one combination => automatic allocation
    if (count($args['combinations']) == 1) {
      $cost = reset($args['combinations']);
      $this->actPay($cost, true);
    } elseif (empty($args['combinations'])) {
      // if we are in harvest we process harvest pay
      if ($this->isHarvest() && $harvestFlag) {
        $this->actPayHarvest();
      } else {
        throw new \BgaVisibleSystemException("No option to pay");
      }
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

    $player = Players::getActive();
    if (isset($cost['card'])) {
      // Paying by returning a card
      $card = PlayerCards::get($cost['card']);
      $card->returnToBoard();
      Notifications::payWithCard($player, $card, $args['source']);
    } elseif (isset($this->getCtxArgs()['to'])) {
      // Payment to a player
      $moved = [];
      foreach ($cost as $resource => $amount) {
        if ($resource == 'sources') {
          continue;
        }
        $moved = array_merge($moved, $player->payResourceTo($this->getCtxArgs()['to'], $resource, $amount));
      }
      if (!empty($moved)) {
        Notifications::payResourcesTo(
          $player,
          $moved,
          $args['source'],
          $cost['sources'] ?? [],
          $args['cardNames'],
          Players::get($this->getCtxArgs()['to'])
        );
      }
    } else {
      // "Normal" payment with ressources
      // Delete meeples and notify
      $deleted = [];
      foreach ($cost as $resource => $amount) {
        if ($resource == 'sources') {
          continue;
        }
        $deleted = array_merge($deleted, $player->useResource($resource, $amount));
      }
      if (!empty($deleted)) {
        Notifications::payResources($player, $deleted, $args['source'], $cost['sources'] ?? [], $args['cardNames']);
      }
    }

    Notifications::updateDropZones($player);
    $player->forceReorganizeIfNeeded();

    // Resolve the node
    $this->resolveAction(['cost' => $cost]);
  }

  /***************************************
   ***************************************
   ************** HARVEST  ***************
   ***************************************
   **************************************/
  public function getHarvestDescription()
  {
    $player = Players::getActive();
    // if (!isset($this->getCtxArgs()['costs']['fees'])) {
    //   throw new \feException(print_r(\debug_print_backtrace()));
    // }
    $cost = $this->getCtxArgs()['costs']['fees'][0];
    $begging = 0;
    foreach ($cost as $resource => $amount) {
      if (in_array($resource, ['nb', 'sources', 'sourcesDesc'])) {
        continue;
      }
      $reserve = $player->countReserveResource($resource);
      if ($reserve < $amount) {
        $cost[$resource] = $reserve;
        $begging += $amount - $reserve;
      }
    }

    $desc = [
      'log' => clienttranslate('Pay ${resources_desc} to feed your family'),
      'args' => [
        'resources_desc' => Utils::resourcesToStr($cost),
      ],
    ];
    if ($begging > 0) {
      $desc['log'] = clienttranslate('Pay ${resources_desc} and take ${n} beggar cards to feed your family');
      $desc['args']['n'] = $begging;
    }

    return $desc;
  }

  public function actPayHarvest()
  {
    // Sanity checks
    self::checkAction('actPay', true);
    $cost = $this->getCtxArgs()['costs']['fees'][0];

    $player = Players::getActive();
    $deleted = [];
    $begging = 0;
    foreach ($cost as $resource => $amount) {
      if (in_array($resource, ['nb', 'sources', 'sourcesDesc'])) {
        continue;
      }
      $reserve = $player->countReserveResource($resource);
      if ($reserve < $amount) {
        $begging += $amount - $reserve;
        $amount = $reserve;
      }

      $deleted = array_merge($deleted, $player->useResource($resource, $amount));
    }

    Notifications::payResources($player, $deleted, clienttranslate('Harvest'));
    if ($begging != 0) {
      $created = $player->createResourceInReserve(BEGGING, $begging);
      Notifications::begging($player, $created);
    }

    // Resolve the node
    $this->resolveAction($cost);
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
  public static function canPayFee($player, $costs)
  {
    return self::canBuy($player, $costs, 0);
  }

  public static function canBuy($player, $costs, $n = 1)
  {
    // Handle major improvements that can be bought with another card
    if (isset($costs['cards'])) {
      $playerCards = $player->getCards($costs['cards']['type'])->getIds();
      foreach ($costs['cards']['list'] as $cardId) {
        if (in_array($cardId, $playerCards)) {
          return true;
        }
      }
    }

    // Compute all buyable combinations
    // NOTICE : can't use maxBuyableAmount since there can be gaps in buyable amounts !!
    $combinations = self::computeAllBuyableCombinations($player, $costs);
    return \array_reduce(
      $combinations,
      function ($acc, $combination) use ($n) {
        return $acc || ($combination['nb'] ?? -1) == $n;
      },
      false
    );
  }

  public static function maxBuyableAmount($player, $costs)
  {
    // Compute all buyable combinations
    $combinations = self::computeAllBuyableCombinations($player, $costs);

    // Reduce to get the max
    return \array_reduce(
      $combinations,
      function ($acc, $combination) {
        return max($acc, $combination['nb'] ?? -1);
      },
      -1 // -1 instead of 0 to distinguish the case where player can afford the fee or not
    );
  }

  /**
   * Compute all the possibles combinations that a player can buy
   * @param $target (opt) : keep only combinations with nb == target
   */
  protected static function computeAllBuyableCombinations($player, $costs, $target = null, $ignoreResources = false)
  {
    $reserve = $player->getExchangeResources();

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

    // possibility to exchange one card for some major improvements
    if (isset($costs['cards']['list']) && !empty($costs['cards']['list'])) {
      $cards = $player->getCards()->getIds();
      foreach ($cards as $card) {
        if (in_array($card, $costs['cards']['list'])) {
          $combinations[] = ['nb' => 1, 'card' => $card];
        }
      }
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
