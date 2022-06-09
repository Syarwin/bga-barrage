<?php
namespace BRG\Companies;

class USA extends \BRG\Models\Company
{
  public function __construct($row)
  {
    $this->id = \COMPANY_USA;
    $this->cname = clienttranslate('USA');
    $this->boardIncomes = [
      BASE => [
        2 => [ROTATE_WHEEL => 1],
        4 => [CREDIT => 6],
        5 => [VP => 7],
      ],
      ELEVATION => [
        2 => [\ANY_MACHINE => 1],
        4 => [VP => 2, FLOW_DROPLET => 2],
        5 => [VP => 7],
      ],
      CONDUIT => [
        2 => [VP => 3],
        4 => [ROTATE_WHEEL => 1, VP => 2],
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
