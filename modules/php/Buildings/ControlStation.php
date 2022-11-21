<?php
namespace BRG\Buildings;
use BRG\Helpers\FlowConvertor;

class ControlStation extends Building
{
  public function getName()
  {
    return clienttranslate('Control Station');
  }

  public function getCost()
  {
    return [
      EXCAVATOR => 4,
      MIXER => 3,
    ];
  }

  public function getVp()
  {
    return 5;
  }

  protected function getCentralIcon()
  {
    return [
      'i' => '<PRODUCTION>[+3]',
      't' => clienttranslate(
        'Perform a production action with a +3 bonus. You can still apply the activated bonuses and special ability of your Company board.'
      ),
    ];
  }

  protected function getFlow()
  {
    return [
      [
        'action' => PRODUCE,
        'args' => ['bonus' => 3],
      ],
    ];
  }

  protected function getEngineerSpaces()
  {
    return [2, '2c', '2c', '2c'];
  }
}
