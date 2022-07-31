<?php
namespace BRG\TechTiles;

use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Meeples;
use BRG\Map;

/*
 * Level 3 conduit
 */

class L3Conduit extends AdvancedTile
{
  protected $structureType = CONDUIT;
  public function getDescs()
  {
    $descs = parent::getDescs();
    $descs[] = clienttranslate(
      'When you use this tile, produce a number of Energy Units equal to the value of the Conduit you have just built, multiplied by 2.'
    );
    $descs[] = clienttranslate(
      'The energy produced is recorded on the Energy Track and can be used to fulfill a Contract.'
    );
    return $descs;
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
          'args' => [ENERGY => 2 * $slot['production'], 'noReduction' => true],
        ],
      ],
    ];
  }
}
