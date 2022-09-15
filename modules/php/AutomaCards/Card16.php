<?php
namespace BRG\AutomaCards;

class Card16 extends \BRG\Models\AutomaCard
{
  public function getFlow()
  {
    return [
      [
        'nEngineers' => 2,
        'type' => \PRODUCE,
        'contract' => \CONTRACT_RED,
      ],
      [
        'nEngineers' => 1,
        'type' => \TAKE_CONTRACT,
        'contract' => \CONTRACT_RED,
        'energy' => 2,
      ],
      [
        'nEngineers' => 2,
        'type' => \CONSTRUCT,
        'structure' => \POWERHOUSE,
      ],
      [
        'nEngineers' => 1,
        'type' => \GAIN_MACHINE,
        'vp' => -3,
        'condition' => NOT_LAST_ROUND,
        'machines' => [\MIXER => 1],
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
      BASE => [AI_CRITERION_BASE_HOLD_WATER, \AI_CRITERION_BASE_MAX_CONDUIT, \AI_CRITERION_BASE_POWERHOUSE, 9],
      CONDUIT => [
        AI_CRITERION_CONDUIT_POWERHOUSE_REVERSE,
        \AI_CRITERION_CONDUIT_SECOND_HIGHEST,
        AI_CRITERION_CONDUIT_BARRAGE_REVERSE,
        '8R',
      ],
      POWERHOUSE => [
        AI_CRITERION_POWERHOUSE_BARRAGE,
        AI_CRITERION_POWERHOUSE_CONDUIT,
        AI_CRITERION_POWERHOUSE_BARRAGE_WATER,
        12,
      ],
      PLACE_DROPLET => ['C', 'B', 'A', 'D'],
    ];
  }
}
