<?php
namespace BRG\Companies;

class Netherlands extends \BRG\Models\Company
{
  public function __construct($row)
  {
    $this->id = \COMPANY_NETHERLANDS;
    $this->name = clienttranslate('Netherlands');

    parent::__construct($row);
  }
}
