<?php
namespace BRG\Buildings;
use BRG\Helpers\FlowConvertor;
use BRG\Helpers\Utils;

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
      'i' => '<COST:1><ARROW><ENERGY:4>',
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
          'args' => [
            'nb' => 1,
            'costs' => Utils::formatCost([CREDIT => 1]),
            'source' => $this->getName(),
          ],
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
