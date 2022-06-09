<?php
namespace BRG\Companies;

class Germany extends \BRG\Models\Company
{
  public function __construct($row)
  {
    $this->id = \COMPANY_GERMANY;
    $this->cname = clienttranslate('Germany');
    $this->boardIncomes = [
      BASE => [
        2 => [VP => 3],
        4 => [ANY_MACHINE => 2],
        5 => [VP => 7],
      ],
      ELEVATION => [
        2 => [CREDIT => 3],
        4 => [VP => 2, ENERGY => 2],
        5 => [VP => 7],
      ],
      CONDUIT => [
        2 => [ROTATE_WHEEL => 1],
        4 => [VP => 2, FLOW_DROPLET => 2],
        5 => [VP => 7],
      ],
      POWERHOUSE => [
        2 => [PRODUCTION_BONUS => 1],
        3 => [], //SPECIAL_POWER => SPECIAL_POWER_USA],
        4 => [PRODUCTION_BONUS => 2],
      ],
    ];
    parent::__construct($row);
  }
}
