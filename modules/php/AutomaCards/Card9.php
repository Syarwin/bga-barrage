<?php
namespace BRG\AutomaCards;

class Card9 extends \BRG\Models\AutomaCard
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
        'structure' => \CONDUIT,
        'constraints' => ['min' => 3],
      ],
      [
        'nEngineers' => 2,
        'type' => \CONSTRUCT,
        'structure' => \ELEVATION,
      ],
      [
        'nEngineers' => 1,
        'type' => \ROTATE_WHEEL,
        'n' => 2,
        'vp' => -2,
      ],
      [
        'nEngineers' => 2,
        'type' => EXTERNAL_WORK,
        'order' => [3, 2, 1],
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
      BASE => [AI_CRITERION_BASE_PAYING_SLOT, \AI_CRITERION_BASE_MAX_CONDUIT, \AI_CRITERION_BASE_POWERHOUSE, 1],
      CONDUIT => [AI_CRITERION_CONDUIT_BARRAGE, AI_CRITERION_CONDUIT_POWERHOUSE, AI_CRITERION_CONDUIT_HIGHEST, '5L'],
      POWERHOUSE => [
        AI_CRITERION_POWERHOUSE_CONDUIT,
        AI_CRITERION_POWERHOUSE_BARRAGE,
        AI_CRITERION_POWERHOUSE_BARRAGE_WATER,
        8,
      ],
      PLACE_DROPLET => ['A', 'B', 'C', 'D'],
    ];
  }
}
