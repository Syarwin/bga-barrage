<?php
namespace BRG\Officers;

class Tommaso extends \BRG\Models\Officer
{
  public function __construct($company)
  {
    parent::__construct($company);
    $this->id = \XO_TOMMASO;
    $this->name = clienttranslate('Tommaso Battista');
    $this->description = clienttranslate(
      'Replace one of your 12 starting Engineers with the Architect at the beginning of the game. The Architect is a special Engineer. If you place the Architect in an action space requiring only one Engineer, you can immediately take another turn. If you place the Architect together with other Engineers in an action space requiring two or three Engineers, you do not activate this special ability.'
    );
  }

  public function isAvailable()
  {
    return false;
  }
}
