<?php
namespace BRG\Companies;

class Italy extends \BRG\Models\Company
{
  public function __construct($row)
  {
    $this->id = \COMPANY_ITALY;
    $this->name = clienttranslate('Italy');

    parent::__construct($row);
  }
}
