<?php
namespace BRG\Buildings;

class CustomerOffice extends Building
{
  public function getName()
  {
    return clienttranslate('Customer Officer');
  }

  public function getCost()
  {
    return [
      EXCAVATOR => 3,
      MIXER => 5,
    ];
  }

  public function getVp()
  {
    return 7;
  }

  protected function getCentralIcon()
  {
    return [
      'i' => '<COST:3><ARROW><FULFILL_CONTRACT>',
      't' => clienttranslate(
        'Pay 3 Credits to fulfill one face up Contract in your supply. You don’t need to produce energy to get the reward.'
      ),
    ];
  }

  protected function getFlow()
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        [
          'action' => PAY,
          'args' => [CREDIT => 3],
        ],
        [
          'action' => \FULFILL_CONTRACT,
          'args' => [
            'n' => null,
          ],
        ],
      ],
    ];
  }

  protected function getEngineerSpaces()
  {
    return [2, '2c', '2c', '2c'];
  }
}
