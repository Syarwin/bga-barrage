<?php
namespace BRG\TechTiles;

use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Meeples;
use BRG\Map;

/*
 * Level 1 powerhouse
 */

class L1Powerhouse extends \BRG\TechTiles\BasicTile
{
  public function canConstruct($structure)
  {
    return $structure == \POWERHOUSE;
  }

  public function isAnyTime()
  {
    return true;
  }

  public function getAnyTimeDesc()
  {
    return clienttranslate('Construct a <POWERHOUSE> without <ENGINEER>');
  }

  public function getPowerFlow($slot)
  {
    return [
      'action' => CONSTRUCT,
      'args' => ['type' => POWERHOUSE, 'tileId' => $this->id],
    ];
  }
}
