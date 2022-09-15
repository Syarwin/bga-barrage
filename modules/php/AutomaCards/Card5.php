<?php
namespace BRG\AutomaCards;

class Card5 extends \BRG\Models\AutomaCard
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
        'n' => 2,
        'flow' => false,
      ],
      [
        'nEngineers' => 2,
        'type' => \CONSTRUCT,
        'structure' => \ELEVATION,
      ],
      [
        'nEngineers' => 2,
        'type' => \CONSTRUCT,
        'structure' => CONDUIT,
      ],
      [
        'nEngineers' => 2,
        'type' => \ROTATE_WHEEL,
        'n' => 3,
        'vp' => -5,
      ],
      [
        'nEngineers' => 2,
        'type' => EXTERNAL_WORK,
        'order' => [2, 3, 1],
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
      BASE => [\AI_CRITERION_BASE_MAX_CONDUIT, \AI_CRITERION_BASE_POWERHOUSE, \AI_CRITERION_BASE_POWERHOUSE_WATER, 6],
      CONDUIT => [AI_CRITERION_CONDUIT_POWERHOUSE, AI_CRITERION_CONDUIT_BARRAGE, AI_CRITERION_CONDUIT_HIGHEST, '3L'],
      POWERHOUSE => [
        \AI_CRITERION_POWERHOUSE_HILL_5,
        AI_CRITERION_POWERHOUSE_CONDUIT,
        AI_CRITERION_POWERHOUSE_BARRAGE,
        5,
      ],
      PLACE_DROPLET => ['D', 'C', 'B', 'A'],
    ];
  }
}
