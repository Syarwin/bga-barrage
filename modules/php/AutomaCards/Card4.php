<?php
namespace BRG\AutomaCards;

class Card4 extends \BRG\Models\AutomaCard
{
  public function getFlow()
  {
    return [
      [
        'nEngineers' => 1,
        'type' => \PRODUCE,
        'contract' => \CONTRACT_GREEN,
        'bonus' => -1,
      ],
      [
        'nEngineers' => 2,
        'type' => \CONSTRUCT,
        'structure' => BASE,
        'constraints' => [\MOUNTAIN],
      ],
      [
        'nEngineers' => 1,
        'type' => GAIN_MACHINE,
        'vp' => -2,
        'machines' => [\EXCAVATOR => 1],
        'condition' => NOT_LAST_ROUND,
      ],
      [
        'nEngineers' => 2,
        'type' => EXTERNAL_WORK,
        'order' => [1, 2, 3],
      ],

      [
        'nEngineers' => 1,
        'type' => \ROTATE_WHEEL,
        'n' => 1,
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
      BASE => [\AI_CRITERION_BASE_MAX_CONDUIT, \AI_CRITERION_BASE_POWERHOUSE, \AI_CRITERION_BASE_POWERHOUSE_WATER, 1],
      CONDUIT => [AI_CRITERION_CONDUIT_HIGHEST, AI_CRITERION_CONDUIT_BARRAGE, AI_CRITERION_CONDUIT_POWERHOUSE, '1A'],
      POWERHOUSE => [
        AI_CRITERION_POWERHOUSE_BARRAGE_WATER,
        AI_CRITERION_POWERHOUSE_CONDUIT,
        AI_CRITERION_POWERHOUSE_BARRAGE,
        5,
      ],
      PLACE_DROPLET => ['A', 'B', 'C'],
    ];
  }
}
