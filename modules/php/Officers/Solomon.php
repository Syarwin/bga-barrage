<?php
namespace BRG\Officers;

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
}
