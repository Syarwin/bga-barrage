<?php
namespace BRG\Companies;

class Germany extends \BRG\Models\Company
{
  public function __construct($row)
  {
    $this->id = \COMPANY_GERMANY;
    $this->cname = clienttranslate('Germany');

    parent::__construct($row);
  }
}
