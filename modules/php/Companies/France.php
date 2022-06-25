<?php
namespace BRG\Companies;

class France extends \BRG\Models\Company
{
  public function __construct($row)
  {
    $this->id = \COMPANY_FRANCE;
    $this->cname = clienttranslate('France');
    $this->boardIncomes = [
      BASE => [
        2 => [ANY_MACHINE => 1],
        4 => [ROTATE_WHEEL => 2],
        5 => [VP => 7],
      ],
      ELEVATION => [
        2 => [VP => 3],
        4 => [VP => 2, \ANY_MACHINE => 1],
        5 => [VP => 7],
      ],
      CONDUIT => [
        2 => [CREDIT => 3],
        4 => [ENERGY => 4],
        5 => [VP => 7],
      ],
      POWERHOUSE => [
        2 => [PRODUCTION_BONUS => 1],
        3 => [SPECIAL_POWER => SPECIAL_POWER_FRANCE],
        4 => [PRODUCTION_BONUS => 2],
      ],
    ];
    parent::__construct($row);
  }

  public function getContractReduction()
  {
    return 3;
  }
}
