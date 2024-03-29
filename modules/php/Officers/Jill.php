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

  public function applyConstructCostModifier(&$costs, $slot)
  {
    if ($slot['type'] == CONDUIT) {
      Utils::addCost($costs['costs'], [MIXER => $slot['production'], 'nb' => WHOLE_COST], $this->name);
    }
  }
}
