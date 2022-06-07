<?php
namespace BRG\TechTiles;

use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Meeples;
use BRG\Map;

/*
 * Level 1 conduit
 */

class L1Conduit extends \BRG\TechTiles\BasicTile
{
  public function canConstruct($structure)
  {
    return $structure == \CONDUIT;
  }

  public function getPowerFlow($slot)
  {
    return [
      'action' => GAIN,
      'args' => [CREDIT => $slot['production'] * 2],
      'source' => clienttranslate('Advanced tile\'s effect)'),
    ];
  }
}
