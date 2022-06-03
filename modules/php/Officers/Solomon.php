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
    foreach ($costs['trades'] as &$c) {
      foreach ($c as $type => &$value) {
        if ($type == 'nb') {
          continue;
        }
        $c[$type] = 1;
      }
    }
    Utils::addCost($costs, [CREDIT => 3, 'nb' => 1], $this->name);
    return $costs;
  }
}
