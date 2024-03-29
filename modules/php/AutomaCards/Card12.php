<?php
namespace BRG\AutomaCards;

class Card12 extends \BRG\Models\AutomaCard
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
        'structure' => \CONDUIT,
        'constraints' => ['min' => 4],
      ],
      [
        'nEngineers' => 2,
        'type' => \GAIN_MACHINE,
        'vp' => -4,
        'condition' => NOT_LAST_ROUND,
        'machines' => [\EXCAVATOR => 2],
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
        'type' => GAIN_VP,
        'vp' => 1,
      ],
    ];
  }

  public function getCriteria()
  {
    return [
      BASE => [AI_CRITERION_BASE_BASIN, AI_CRITERION_BASE_POWERHOUSE, \AI_CRITERION_BASE_MAX_CONDUIT, 4],
      CONDUIT => [AI_CRITERION_CONDUIT_BARRAGE, AI_CRITERION_CONDUIT_POWERHOUSE, AI_CRITERION_CONDUIT_HIGHEST, '6R'],
      POWERHOUSE => [
        AI_CRITERION_POWERHOUSE_CONDUIT,
        AI_CRITERION_POWERHOUSE_BARRAGE,
        AI_CRITERION_POWERHOUSE_BARRAGE_WATER,
        10,
      ],
      PLACE_DROPLET => ['D', 'A', 'B', 'C'],
    ];
  }
}
