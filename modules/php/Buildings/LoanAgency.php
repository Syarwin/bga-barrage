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
      'i' => '<LOAN_AGENCY>',
      't' => clienttranslate(
        'Take an available External Work tile and immediately receive its reward. Instead of discarding Machineries, you must pay an amount of Credits equal to the number of Machineries required by the tile multiplied per 2.'
      ),
    ];
  }

  public function getFlow()
  {
    return [
      'type' => \NODE_XOR,
      'childs' => [
        [
          'action' => EXTERNAL_WORK,
          'args' => [
            'position' => 1,
            'cost' => CREDIT,
          ],
        ],
        [
          'action' => EXTERNAL_WORK,
          'args' => [
            'position' => 2,
            'cost' => CREDIT,
          ],
        ],
        [
          'action' => EXTERNAL_WORK,
          'args' => [
            'position' => 3,
            'cost' => CREDIT,
          ],
        ],
      ],
    ];
  }

  public function getEngineerSpaces()
  {
    return [1, 2, 2, 2];
  }
}
