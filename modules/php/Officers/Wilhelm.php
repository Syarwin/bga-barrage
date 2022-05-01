<?php
namespace BRG\Officers;

class Wilhelm extends \BRG\Models\Officer
{
  public function __construct($company)
  {
    parent::__construct($company);
    $this->id = \XO_WILHELM;
    $this->name = clienttranslate('Wilhelm Adler');
    $this->description = clienttranslate(
      'Your Bases always cost 3 Excavators, no matter which area of the Map you build them. Your Elevations costs depend on the area of the Map, as for the basic rule.'
    );
  }
}
