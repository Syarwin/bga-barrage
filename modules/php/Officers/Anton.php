<?php
namespace BRG\Officers;

class Anton extends \BRG\Models\Officer
{
  public function __construct($company)
  {
    parent::__construct($company);
    $this->id = \XO_ANTON;
    $this->name = clienttranslate('Anton Krylov');
    $this->description = clienttranslate(
      'Take the depicted special Technology tile at the beginning of the game. When you use this Technology tile you can copy another Technology tile of your choice on your Construction Wheel. This special tile copies both the main building effect and the special effect of the copied tile.'
    );
  }
}
