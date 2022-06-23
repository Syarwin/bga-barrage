<?php
namespace BRG\Companies;

class Italy extends \BRG\Models\Company
{
  public function __construct($row)
  {
    $this->id = \COMPANY_ITALY;
    $this->cname = clienttranslate('Italy');
    $this->boardIncomes = [
      BASE => [
        2 => [CREDIT => 3],
        4 => [VP => 5],
        5 => [VP => 7],
      ],
      ELEVATION => [
        2 => [ROTATE_WHEEL => 1],
        4 => [ENERGY => 4],
        5 => [VP => 7],
      ],
      CONDUIT => [
        2 => [ANY_MACHINE => 1],
        4 => [VP => 2, CREDIT => 3],
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
