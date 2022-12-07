<?php
namespace BRG\Buildings;

class FinancialDivision extends Building
{
  public function getName()
  {
    return clienttranslate('Financial Division');
  }

  public function getCost()
  {
    return [
      EXCAVATOR => 3,
      MIXER => 4,
    ];
  }

  public function getVp()
  {
    return 6;
  }

  public function getCentralIcon()
  {
    return [
      'i' => '<CREDIT:5>',
      't' => clienttranslate('Gain 5 Credits.'),
    ];
  }

  public function getFlow()
  {
    return [
      'action' => GAIN,
      'args' => [CREDIT => 5],
    ];
  }

  public function getEngineerSpaces()
  {
    return [1, 2, 2, 2];
  }
}
