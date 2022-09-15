<?php
namespace BRG\AutomaCards;

class Card19 extends \BRG\Models\AutomaCard
{
  public function getFlow()
  {
    return [
      [
        'nEngineers' => 2,
        'type' => \PRODUCE,
        'contract' => \CONTRACT_YELLOW,
        'bonus' => 2,
      ],
      [
        'nEngineers' => 2,
        'type' => \CONSTRUCT,
        'structure' => \CONDUIT,
      ],
      [
        'nEngineers' => 1,
        'type' => \CONSTRUCT,
        'structure' => BUILDING,
        'constraints' => 'up',
      ],
      [
        'nEngineers' => 2,
        'type' => EXTERNAL_WORK,
        'order' => [3, 2, 1],
      ],
      [
        'nEngineers' => 1,
        'type' => \PATENT,
        'structure' => \BUILDING,
        'vp' => -2,
      ],
      [
        'nEngineers' => 2,
        'type' => \GAIN_MACHINE,
        'vp' => -5,
        'machines' => [\EXCAVATOR => 1, MIXER => 1],
        'condition' => NOT_LAST_ROUND,
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
      BASE => [\AI_CRITERION_BASE_POWERHOUSE_WATER, \AI_CRITERION_BASE_MAX_CONDUIT, \AI_CRITERION_BASE_HOLD_WATER, 5],
      CONDUIT => [
        \AI_CRITERION_CONDUIT_SECOND_HIGHEST,
        AI_CRITERION_CONDUIT_POWERHOUSE,
        AI_CRITERION_CONDUIT_BARRAGE,
        '10L',
      ],
      POWERHOUSE => [
        AI_CRITERION_POWERHOUSE_BARRAGE,
        AI_CRITERION_POWERHOUSE_CONDUIT,
        AI_CRITERION_POWERHOUSE_BARRAGE_WATER,
        12,
      ],
      PLACE_DROPLET => ['C', 'D', 'A', 'B'],
    ];
  }
}
