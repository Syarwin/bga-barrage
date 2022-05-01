<?php
namespace BRG\Officers;

class Gennaro extends \BRG\Models\Officer
{
  public function __construct($company)
  {
    parent::__construct($company);
    $this->id = \XO_GENNARO;
    $this->name = clienttranslate('Gennaro Grasso');
    $this->description = clienttranslate(
      'You have an extra Construction space. You can use it one time per round and it works exactly like the Construciton spaces on the Company boards. This means that you could make five construction actions per round - or save Engineers and/or Credits by using this action space instead of an action space on your Company Board.'
    );
  }

  public function isAvailable()
  {
    return false;
  }
}
