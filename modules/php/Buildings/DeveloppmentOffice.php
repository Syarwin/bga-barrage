<?php
namespace BRG\Buildings;

class DeveloppmentOffice extends Building
{
  public function getName()
  {
    return clienttranslate('Developpment Office');
  }

  public function getCost()
  {
    return [
      EXCAVATOR => 1,
      MIXER => 2,
    ];
  }

  public function getVp()
  {
    return 3;
  }

  public function getCentralIcon()
  {
    return [
      'i' => '<RETRIEVE_TECH_TILE>',
      't' => clienttranslate(
        'Take a Technology tile from your Construction Wheel and place it back in your personal supply. You cannot take any invested Machinery from that segment.'
      ),
    ];
  }

  public function getFlow()
  {
    return [
      'action' => RETRIEVE_FROM_WHEEL,
      'args' => ['type' => TECH_TILE],
    ];
  }

  public function getEngineerSpaces()
  {
    return [1, '1c', '1c', '1c'];
  }
}
