<?php
namespace BRG\Officers;

use BRG\Helpers\Utils;

class Solomon extends \BRG\Models\Officer
{
  public function __construct($company)
  {
    parent::__construct($company);
    $this->id = \XO_SOLOMON;
    $this->name = clienttranslate('Solomon P. Jordan');
    $this->description = clienttranslate(
      'Whenever you build a structure you can pay 3 Credits instead of a requested Machinery. You can use this ability as many times as you want, even paying only in Credits. Put the Credits in the general supply, not in the Construction Wheel.'
    );
  }

  public function getCostModifier($slot, $machine, $n)
  {
    $costs = parent::getCostModifier($slot, $machine, $n);
    // throw new \feException(print_r($costs));
    if ($slot['type'] == CONDUIT) {
      foreach ($costs['trades'] as &$c) {
        foreach ($c as $machine => $nb) {
          if ($machine == 'nb') {
            continue;
          }
          $c[$machine] = 1;
        }
      }
    }
    Utils::addCost($costs, [CREDIT => 3, 'nb' => 1], $this->name);

    return $costs;
  }

  public function getUnitsModifier($slot, $machine, $n)
  {
    if ($slot['type'] == CONDUIT) {
      return parent::getUnitsModifier($slot, $machine, $n) * 2;
    }
    return parent::getUnitsModifier($slot, $machine, $n);
  }
}
