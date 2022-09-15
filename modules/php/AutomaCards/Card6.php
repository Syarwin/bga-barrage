<?php
namespace BRG\AutomaCards;

class Card6 extends \BRG\Models\AutomaCard
{
  public function getFlow()
  {
    return [
      [
        'nEngineers' => 2,
        'type' => \PRODUCE,
        'contract' => \CONTRACT_GREEN,
      ],
      [
        'nEngineers' => 1,
        'type' => \PLACE_DROPLET,
        'n' => 1,
        'flow' => true,
      ],
      [
        'nEngineers' => 2,
        'type' => \CONSTRUCT,
        'structure' => \ELEVATION,
      ],
      [
        'nEngineers' => 2,
        'type' => \CONSTRUCT,
        'structure' => BASE,
      ],
      [
        'nEngineers' => 2,
        'type' => EXTERNAL_WORK,
        'order' => [3, 2, 1],
      ],
      [
        'nEngineers' => 1,
        'type' => \ROTATE_WHEEL,
        'n' => 1,
      ],
      [
        'nEngineers' => 1,
        'type' => GAIN_MACHINE,
        'vp' => -3,
        'condition' => NOT_LAST_ROUND,
      ],
      [
        'nEngineers' => 1,
        'type' => GAIN_VP,
        'vp' => 1,
      ],
    ];
  }

  public function getCriteria()
  {
    return [
      BASE => [\AI_CRITERION_BASE_MAX_CONDUIT, \AI_CRITERION_BASE_POWERHOUSE, \AI_CRITERION_BASE_PAYING_SLOT, 7],
      CONDUIT => [AI_CRITERION_CONDUIT_POWERHOUSE, AI_CRITERION_CONDUIT_BARRAGE, AI_CRITERION_CONDUIT_HIGHEST, '3R'],
      POWERHOUSE => [
        \AI_CRITERION_POWERHOUSE_HILL_6,
        AI_CRITERION_POWERHOUSE_CONDUIT,
        AI_CRITERION_POWERHOUSE_BARRAGE,
        6,
      ],
      PLACE_DROPLET => ['C', 'B', 'A', 'D'],
    ];
  }
}
