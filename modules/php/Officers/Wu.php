<?php
namespace BRG\Officers;

class Wu extends \BRG\Models\Officer
{
  public function __construct($company)
  {
    parent::__construct($company);
    $this->id = \XO_WU;
    $this->name = clienttranslate('Wu Fang');
    $this->description = clienttranslate(
      "You have a personal action space. When it's your turn during the Actions Phase, you can place 1 Engineer on this space to immediately produce an amount of Energy Units equal to the number of Water Drops currently held in your Dams, without moving them; don't add any bonus to this production. You can use these Energy Units to fulfill a Contract and to advance on the Energy Track."
    );
  }

  public function isAvailable()
  {
    return false;
  }
}
