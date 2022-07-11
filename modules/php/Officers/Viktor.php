<?php
namespace BRG\Officers;

class Viktor extends \BRG\Models\Officer
{
  public function __construct($company)
  {
    parent::__construct($company);
    $this->id = \XO_VIKTOR;
    $this->name = clienttranslate('Viktor Fiesler');
    $this->description = clienttranslate(
      'When you produce less than 4 Energy Units with a production action, you produce 4 Energy Units instead. Multiply the total of Water Drops by the value of the Conduit that you are using: if the total is three or less you should consider the total to be 4. Then you must apply any bonus/malus of the action symbol and any bonus of your Company Board.'
    );
  }
}
