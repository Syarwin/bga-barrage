<?php
namespace BRG\Officers;
use BRG\Core\Globals;

class Margot extends \BRG\Models\Officer
{
  public function __construct($company)
  {
    parent::__construct($company);
    $this->id = \XO_MARGOT;
    $this->name = clienttranslate('Margot Fouche');
    $this->description = clienttranslate(
      "You have a personal action space on this Executive Officer tile. When it's your turn during the Actions Phase, you can place 1 Engineer here to perform the special action of a Private Building that you have activated. You still can perform the same Private Building action using its normal action spaces. Therefore, this Executive Officer allows you to perform the same Private Building special action twice in the same round."
    );
  }

  public function isAvailable()
  {
    return Globals::isLWP();
  }
}
