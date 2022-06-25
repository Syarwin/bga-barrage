<?php
namespace BRG\Companies;

class Netherlands extends \BRG\Models\Company
{
  public function __construct($row)
  {
    $this->id = \COMPANY_NETHERLANDS;
    $this->cname = clienttranslate('Netherlands');
    $this->boardIncomes = [
      BASE => [
        2 => [PLACE_DROPLET => 2],
        4 => [ROTATE_WHEEL => 2],
        5 => [VP => 7],
      ],
      ELEVATION => [
        2 => [CREDIT => 3],
        4 => [VP => 2, FLOW_DROPLET => 2],
        5 => [VP => 7],
      ],
      CONDUIT => [
        2 => [ENERGY => 2],
        4 => [ANY_MACHINE => 2],
        5 => [VP => 7],
      ],
      POWERHOUSE => [
        2 => [PRODUCTION_BONUS => 1],
        3 => [SPECIAL_POWER => SPECIAL_POWER_NETHERLANDS],
        4 => [PRODUCTION_BONUS => 2],
      ],
    ];
    parent::__construct($row);
  }
}
