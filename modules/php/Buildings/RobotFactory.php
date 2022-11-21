<?php
namespace BRG\Buildings;
use BRG\Helpers\FlowConvertor;

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

  protected function getCentralIcon()
  {
    return [
      'i' => '<COST:1><ARROW><ANY_MACHINE:2>',
      't' => clienttranslate('Pay 1 Credit to receive 2 Machineries of your choosing.'),
    ];
  }

  protected function getFlow()
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        [
          'action' => PAY,
          'args' => [CREDIT => 1],
        ],
        FlowConvertor::computeRewardFlow([ANY_MACHINE => 2]),
      ],
    ];
  }

  protected function getEngineerSpaces()
  {
    return [2, '2c', '2c', '2c'];
  }
}
