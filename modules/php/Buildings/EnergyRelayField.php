<?php
namespace BRG\Buildings;
use BRG\Helpers\FlowConvertor;

class EnergyRelayField extends Building
{
  public function getName()
  {
    return clienttranslate('Energy Relay Field');
  }

  public function getCost()
  {
    return [
      EXCAVATOR => 4,
      MIXER => 1,
    ];
  }

  public function getVp()
  {
    return 4;
  }

  public function getCentralIcon()
  {
    return [
      'i' => '<COST:1><ENERGY:4>',
      't' => clienttranslate(
        'Pay 1 Credit to move your Energy marker of 4 steps on the Energy Track. As usual, you cannot use this Energy Units to fulfill Contracts.'
      ),
    ];
  }

  public function getFlow()
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        [
          'action' => PAY,
          'args' => [CREDIT => 1],
        ],
        FlowConvertor::computeRewardFlow([ENERGY => 4]),
      ],
    ];
  }

  public function getEngineerSpaces()
  {
    return [1, '1c', '1c', '1c'];
  }
}
