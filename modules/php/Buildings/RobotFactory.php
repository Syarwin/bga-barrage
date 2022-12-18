<?php
namespace BRG\Buildings;
use BRG\Helpers\FlowConvertor;
use BRG\Helpers\Utils;

class RobotFactory extends Building
{
  public function getName()
  {
    return clienttranslate('Robot Factory');
  }

  public function getCost()
  {
    return [
      EXCAVATOR => 1,
      MIXER => 5,
    ];
  }

  public function getVp()
  {
    return 5;
  }

  public function getCentralIcon()
  {
    return [
      'i' => '<COST:1><ARROW><ANY_MACHINE:2>',
      't' => clienttranslate('Pay 1 Credit to receive 2 Machineries of your choosing.'),
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
        FlowConvertor::computeRewardFlow([ANY_MACHINE => 2]),
      ],
    ];
  }

  public function getEngineerSpaces()
  {
    return [2, '2c', '2c', '2c'];
  }
}
