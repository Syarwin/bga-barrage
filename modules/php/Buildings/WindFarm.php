<?php
namespace BRG\Buildings;
use BRG\Helpers\FlowConvertor;

class WindFarm extends Building
{
  public function getName()
  {
    return clienttranslate('Wind Farm');
  }

  public function getCost()
  {
    return [
      EXCAVATOR => 6,
      MIXER => 2,
    ];
  }

  public function getVp()
  {
    return 5;
  }

  public function getCentralIcon()
  {
    return [
      'i' => '<COST:2><ARROW>[5]',
      't' => clienttranslate(
        'Pay 2 Credits to produce 5 Energy Units. Move your Energy marker on the Energy Track by 5 steps. You can use this energy to fulfill a Contract.'
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
          'args' => [CREDIT => 2],
        ],
        FlowConvertor::computeRewardFlow([ENERGY_PRODUCED => 5]),
      ],
    ];
  }

  public function getEngineerSpaces()
  {
    return [2, '2c', '2c', '2c'];
  }
}
