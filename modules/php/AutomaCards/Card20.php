<?php
namespace BRG\AutomaCards;

class Card20 extends \BRG\Models\AutomaCard
{
  public function getFlow()
  {
    return [
      [
        'nEngineers' => 2,
        'type' => \PRODUCE,
        'contract' => \CONTRACT_GREEN,
        'bonus' => 2,
      ],
      [
        'nEngineers' => 2,
        'type' => \CONSTRUCT,
        'structure' => \POWERHOUSE,
      ],
      [
        'nEngineers' => 1,
        'type' => \CONSTRUCT,
        'structure' => BUILDING,
        'constraints' => 'up',
      ],
      [
        'nEngineers' => 2,
        'type' => \GAIN_MACHINE,
        'vp' => -5,
        'machines' => [\EXCAVATOR => 1, MIXER => 1],
        'condition' => NOT_LAST_ROUND,
      ],
      [
        'nEngineers' => 2,
        'type' => EXTERNAL_WORK,
        'order' => [2, 3, 1],
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
      BASE => [\AI_CRITERION_BASE_POWERHOUSE_WATER, \AI_CRITERION_BASE_MAX_CONDUIT, \AI_CRITERION_BASE_HOLD_WATER, 10],
      CONDUIT => [
        \AI_CRITERION_CONDUIT_SECOND_HIGHEST,
        AI_CRITERION_CONDUIT_POWERHOUSE,
        AI_CRITERION_CONDUIT_BARRAGE,
        '10R',
      ],
      POWERHOUSE => [
        AI_CRITERION_POWERHOUSE_BARRAGE,
        AI_CRITERION_POWERHOUSE_CONDUIT,
        AI_CRITERION_POWERHOUSE_BARRAGE_WATER,
        12,
      ],
      PLACE_DROPLET => ['D', 'A', 'B', 'C'],
    ];
  }
}
