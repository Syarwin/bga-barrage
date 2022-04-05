<?php
namespace BRG\Companies;

class France extends \BRG\Models\Company
{
  public function __construct($row)
  {
    $this->id = \COMPANY_FRANCE;
    $this->name = clienttranslate('France');

    parent::__construct($row);
  }
}
