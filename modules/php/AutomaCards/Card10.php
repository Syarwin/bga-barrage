<?php
namespace BRG\AutomaCards;

class Card10 extends \BRG\Models\AutomaCard
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
        'structure' => \CONDUIT,
      ],
      [
        'nEngineers' => 2,
        'type' => \CONSTRUCT,
        'structure' => \POWERHOUSE,
      ],
      [
        'nEngineers' => 2,
        'type' => EXTERNAL_WORK,
        'order' => [1, 2, 3],
      ],
      [
        'nEngineers' => 2,
        'type' => \ROTATE_WHEEL,
        'n' => 3,
        'vp' => -5,
      ],
      [
        'nEngineers' => 1,
        'type' => \GAIN_MACHINE,
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
