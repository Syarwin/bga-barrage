<?php
namespace BRG\Buildings;

class LoanAgency extends Building
{
  public function getName()
  {
    return clienttranslate('Loan Agency');
  }

  public function getCost()
  {
    return [
      EXCAVATOR => 1,
      MIXER => 3,
    ];
  }

  public function getVp()
  {
    return 4;
  }

  public function getCentralIcon()
  {
    return [
      'i' => '<COST:2>xTODO',
      't' => clienttranslate(
        'Take an available External Work tile and immediately receive its reward. Instead of discarding Machineries, you must pay an amount of Credits equal to the number of Machineries required by the tile multiplied per 2.'
      ),
    ];
  }

  public function getFlow()
  {
    // TODO : XOR node with EXTERNAL_WORK => n action, with a special flag for cost
    return [];
  }

  public function getEngineerSpaces()
  {
    return [1, 2, 2, 2];
  }
}
