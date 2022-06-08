<?php
namespace BRG\TechTiles;

use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Meeples;
use BRG\Map;

/*
 * Level 3 conduit
 */

class L3Conduit extends \BRG\TechTiles\BasicTile
{
  public function canConstruct($structure)
  {
    return $structure == \CONDUIT;
  }

  public function getPowerFlow($slot)
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        ['action' => GAIN, 'args' => [ENERGY => 2 * $slot['production']]],
        [
          'action' => \FULFILL_CONTRACT,
          'optional' => true,
          'args' => [ENERGY => 2 * $slot['production']],
        ],
      ],
    ];
  }
}
