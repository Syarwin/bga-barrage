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
}
