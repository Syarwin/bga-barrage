<?php
namespace BRG\Models;

/*
 * Officer: all utility functions concerning an executive officer
 */

class Officer
{
  protected $company;
  protected $id;
  protected $name;
  protected $description;
  protected $contractReduction = 0;

  public function __construct($company)
  {
    $this->company = $company;
  }

  public function getName()
  {
    return $this->name;
  }

  public function isAvailable()
  {
    return true;
  }

  public function getContractReduction()
  {
    return $this->contractReduction;
  }
}
