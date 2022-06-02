<?php
namespace BRG\Officers;
use BRG\Helpers\Utils;

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

  public function getCostModifier($type, $machine, $n)
  {
    if ($type == BASE) {
      return [
        'nb' => 3,
        'costs' => Utils::formatCost([$machine => 3, 'nb' => 3]),
      ];
    }
    return parent::getCostModifier($type, $machine, $n);
  }
}
