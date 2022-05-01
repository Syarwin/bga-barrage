<?php
namespace BRG\Officers;

class Graziano extends \BRG\Models\Officer
{
  public function __construct($company)
  {
    parent::__construct($company);
    $this->id = \XO_GRAZIANO;
    $this->name = clienttranslate('Graziano Del Monte');
    $this->description = clienttranslate(
      'Your level 3 Dams can hold up to 4 Water Drops. Your level 1 and level 2 Dams can hold 1 or 2 Water Drops respectively, as for the basic rules.'
    );
  }
}
