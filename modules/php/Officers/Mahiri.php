<?php
namespace BRG\Officers;

class Mahiri extends \BRG\Models\Officer
{
  public function __construct($company)
  {
    parent::__construct($company);
    $this->id = \XO_MAHIRI;
    $this->name = clienttranslate('Mahiri Sekiso');
    $this->description = clienttranslate(
      'You have a personal special ability that you can activate placing 1 Engineer on the action space of this tile. If you use it a second time during the same roun, you must also pay 3 Credits. When you activate it, you can copy another Executive Officer\'s special ability.'
    );
  }
}
