<?php
namespace BRG\Models;
use BRG\Helpers\Utils;

/*
 * Officer: all utility functions concerning an executive officer
 */

class Officer implements \JsonSerializable
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

  public function jsonSerialize()
  {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'description' => $this->description,
      'startingResources' => $this->getStartingResources(),
    ];
  }

  public function getName()
  {
    return $this->name;
  }

  public function getId()
  {
    return $this->id;
  }

  public function getDescription()
  {
    return $this->description;
  }

  public function isAvailable()
  {
    return true;
  }

  public function getContractReduction()
  {
    return $this->contractReduction;
  }

  public function addActionSpacesUi(&$rows)
  {
  }

  public function addActionSpaces(&$spaces)
  {
  }

  public function applyConstructCostModifier(&$costs, $slot)
  {
  }

  /************** SETUP **************/
  public function getStartingResources()
  {
    return [
      ENGINEER => 12,
      CREDIT => 6,
      EXCAVATOR => 6,
      MIXER => 4,
    ];
  }
}
