<?php
namespace BRG\Officers;

class Jill extends \BRG\Models\Officer
{
  public function __construct($company)
  {
    parent::__construct($company);
    $this->id = \XO_JILL;
    $this->name = clienttranslate("Jill McDowell");
    $this->description = clienttranslate(
      'You can build your Conduits using Concrete Mixers instead of Excavators. If you use this ability the Conduit coses 1 Concrete Mixer multiplied by the Conduit production value. You cannot mix the two Machineries to pay for a Conduit.'
    );
  }
}
