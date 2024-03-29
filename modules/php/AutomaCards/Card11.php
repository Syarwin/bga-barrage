<?php
namespace BRG\AutomaCards;

class Card11 extends \BRG\Models\AutomaCard
{
  public function getFlow()
  {
    return [
      [
        'nEngineers' => 2,
        'type' => \PRODUCE,
        'contract' => \CONTRACT_GREEN,
        'bonus' => 1,
      ],
      [
        'nEngineers' => 1,
        'type' => \TAKE_CONTRACT,
        'contract' => \CONTRACT_GREEN,
        'energy' => 2,
      ],
      [
        'nEngineers' => 2,
        'type' => \CONSTRUCT,
        'structure' => \CONDUIT,
      ],
      [
        'nEngineers' => 1,
        'type' => \PATENT,
        'structure' => \CONDUIT,
        'vp' => -2,
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
      BASE => [AI_CRITERION_BASE_BASIN, AI_CRITERION_BASE_POWERHOUSE, \AI_CRITERION_BASE_MAX_CONDUIT, 3],
      CONDUIT => [AI_CRITERION_CONDUIT_BARRAGE, AI_CRITERION_CONDUIT_POWERHOUSE, AI_CRITERION_CONDUIT_HIGHEST, '6L'],
      POWERHOUSE => [
        AI_CRITERION_POWERHOUSE_CONDUIT,
        AI_CRITERION_POWERHOUSE_BARRAGE,
        AI_CRITERION_POWERHOUSE_BARRAGE_WATER,
        9,
      ],
      PLACE_DROPLET => ['C', 'D', 'A', 'B'],
    ];
  }
}
