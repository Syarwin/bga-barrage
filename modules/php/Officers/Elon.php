<?php
namespace BRG\Officers;

class Elon extends \BRG\Models\Officer
{
  public function __construct($company)
  {
    parent::__construct($company);
    $this->id = \XO_ELON;
    $this->name = clienttranslate('Elon Audia');
    $this->description = clienttranslate(
      'You start the game with no Excavators and no Concrete Mixers; instead, you receive the 8 Excamixers. You can use these special Machineries as wildcards - they count both as Excavators or Concrete Mixers.'
    );
  }

  public function isAvailable()
  {
    return false;
  }
}
