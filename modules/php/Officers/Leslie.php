<?php
namespace BRG\Officers;
use BRG\Core\Globals;

class Leslie extends \BRG\Models\Officer
{
  public function __construct($company)
  {
    parent::__construct($company);
    $this->id = \XO_LESLIE;
    $this->name = clienttranslate('Leslie Spencer');
    $this->description = clienttranslate(
      'Take the depicted Special Technology tile at the beginning of the game. When you perform an External Works action, you can place the required Machineries on your Construction Wheel together with this tile. Then, rotate the Wheel by one segment. You don\'t have to place Engineers in a construction action space on your Company Board, but you still have to place them on the connected External Works action space.'
    );
  }

  public function isAvailable()
  {
    return false; //Globals::isLWP();
  }
}
