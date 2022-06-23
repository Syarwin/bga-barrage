<?php
namespace BRG\Officers;

class Simone extends \BRG\Models\Officer
{
  public function __construct($company)
  {
    parent::__construct($company);
    $this->id = \XO_SIMONE;
    $this->name = clienttranslate('Simone Luciani');
    $this->description = clienttranslate(
      'You can have up to 4 Contract tiles face-up in your personal supply. You can fulfill two or more Contracts with one single production action, as long as the total amount of produced Energy Units is more than or equal to the total amount of energy required by the Contracts.'
    );
  }

  public function isAvailable()
  {
    return false;
  }
}
