<?php
namespace BRG\AutomaCards;

class Card3 extends \BRG\Models\AutomaCard
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
        'nEngineers' => 1,
        'type' => \TAKE_CONTRACT,
        'contract' => \CONTRACT_GREEN,
        'energy' => 2,
      ],
      [
        'nEngineers' => 2,
        'type' => \CONSTRUCT,
        'structure' => \BASE,
      ],
      [
        'nEngineers' => 1,
        'type' => PATENT,
        'structure' => \BASE,
        'vp' => -2,
      ],
      [
        'nEngineers' => 2,
        'type' => EXTERNAL_WORK,
        'order' => [3, 2, 1],
      ],
      [
        'nEngineers' => 1,
        'type' => ROTATE_WHEEL,
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
      BASE => [\AI_CRITERION_BASE_MAX_CONDUIT, \AI_CRITERION_BASE_POWERHOUSE, \AI_CRITERION_BASE_BASIN, 3],
      CONDUIT => [AI_CRITERION_CONDUIT_HIGHEST, AI_CRITERION_CONDUIT_POWERHOUSE, AI_CRITERION_CONDUIT_BARRAGE, '2L'],
      POWERHOUSE => [
        AI_CRITERION_POWERHOUSE_BARRAGE_WATER_REVERSE,
        AI_CRITERION_POWERHOUSE_CONDUIT,
        AI_CRITERION_POWERHOUSE_BARRAGE,
        7,
      ],
      PLACE_DROPLET => ['C', 'D', 'A', 'B'],
    ];
  }
}
