<?php
namespace BRG\Buildings;

class ResearchLab extends Building
{
  public function getName()
  {
    return clienttranslate('Research Lab');
  }

  public function getCost()
  {
    return [
      EXCAVATOR => 2,
      MIXER => 2,
    ];
  }

  public function getVp()
  {
    return 4;
  }

  public function getCentralIcon()
  {
    return [
      'i' => '<RETRIEVE_MACHINERIES>',
      't' => clienttranslate(
        'Choose one segment of your Construction Wheel. Take half of the invested Machineries from that segment and place them back in your personal supply, round down. You can decide which Machineries to take.'
      ),
    ];
  }

  public function getFlow()
  {
    return [
      [
        'action' => RETRIEVE_FROM_WHEEL,
        'args' => ['type' => ANY_MACHINE],
      ],
    ];
  }

  public function getEngineerSpaces()
  {
    return [1, '1c', '1c', '1c'];
  }
}
