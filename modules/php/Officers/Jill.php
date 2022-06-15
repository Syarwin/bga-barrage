<?php
namespace BRG\Officers;

use BRG\Helpers\Utils;

class Jill extends \BRG\Models\Officer
{
  public function __construct($company)
  {
    parent::__construct($company);
    $this->id = \XO_JILL;
    $this->name = clienttranslate('Jill McDowell');
    $this->description = clienttranslate(
      'You can build your Conduits using Concrete Mixers instead of Excavators. If you use this ability the Conduit costs 1 Concrete Mixer multiplied by the Conduit production value. You cannot mix the two Machineries to pay for a Conduit.'
    );
  }

  public function getCostModifier($slot, $machine, $n)
  {
    $costs = parent::getCostModifier($slot, $machine, $n);
    if ($slot['type'] == CONDUIT) {
      // As we cannot combo Mixer & Excavator, change of logic to buy 1 item at cost for the item
      foreach ($costs['trades'] as &$c) {
        foreach ($c as $machine => $nb) {
          if ($machine == 'nb') {
            continue;
          }
          $c[$machine] = $nb * $n;
        }
      }
      Utils::addCost($costs, [MIXER => $n, 'nb' => 1], $this->name);
    }

    return $costs;
  }

  public function getUnitsModifier($slot, $machine, $n)
  {
    if ($slot['type'] == CONDUIT) {
      return 1;
    }
    return parent::getUnitsModifier($slot, $machine, $n);
  }
}
