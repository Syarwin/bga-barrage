<?php
namespace BRG\Companies;

class USA extends \BRG\Models\Company
{
  public function __construct($row)
  {
    $this->id = \COMPANY_USA;
    $this->cname = clienttranslate('USA');

    parent::__construct($row);
  }
}
