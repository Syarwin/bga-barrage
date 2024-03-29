<?php
namespace BRG\Buildings;
use BRG\Helpers\FlowConvertor;

class Cofferdam extends Building
{
  public function getName()
  {
    return clienttranslate('Cofferdam');
  }

  public function getCost()
  {
    return [
      EXCAVATOR => 2,
      MIXER => 1,
    ];
  }

  public function getVp()
  {
    return 3;
  }

  public function getCentralIcon()
  {
    return [
      'i' => '<WATER_DAM:1>',
      't' => clienttranslate(
        'Take 1 Water Drop from the general supply and place it directly on a Neutral Dam or on one of your Personal Dams of your choosing. The dam must be able to hold that additional water drop.'
      ),
    ];
  }

  public function getFlow()
  {
    return [
      'action' => \PLACE_DROPLET,
      'args' => [
        'n' => 1,
        'type' => 'dam',
      ],
    ];
  }

  public function getEngineerSpaces()
  {
    return [1, '1c', '1c', '1c'];
  }
}
