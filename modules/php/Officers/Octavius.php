<?php
namespace BRG\Officers;

class Octavius extends \BRG\Models\Officer
{
  public function __construct($company)
  {
    parent::__construct($company);
    $this->id = \XO_OCTAVIUS;
    $this->name = clienttranslate('Dr. Octavius');
    $this->description = clienttranslate(
      'When producing energy by activating one of your Powerhouses with a Production action, you can use Water Drops from two or more of your Dams (and/or Neutral Dams) connected to the activated Powerhouse.'
    );
  }

  public function isAvailable()
  {
    return false;
  }
}
