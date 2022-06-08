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
      'You can build your Conduits using Concrete Mixers instead of Excavators. If you use this ability the Conduit coses 1 Concrete Mixer multiplied by the Conduit production value. You cannot mix the two Machineries to pay for a Conduit.'
    );
  }

  public function getCostModifier($slot, $machine, $n)
  {
    $costs = parent::getCostModifier($slot, $machine, $n);
    if ($slot['type'] == CONDUIT) {
      Utils::addCost($costs, [MIXER => 1, 'nb' => 1], $this->name);
    }
    return $costs;
  }
}
