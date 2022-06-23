<?php
namespace BRG\TechTiles;

use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Meeples;
use BRG\Map;

/*
 * Level 3 base
 */

class L3Base extends \BRG\TechTiles\BasicTile
{
  public function canConstruct($structure)
  {
    return $structure == BASE;
  }

  public function isAnyTime()
  {
    return true;
  }

  public function getAnyTimeDesc()
  {
    return clienttranslate('Construct a base<BASE> without engineer(s)<ENGINEER> & excavator(s)<EXCAVATOR>');
  }

  public function getPowerFlow($slot)
  {
    return [
      'action' => CONSTRUCT,
      'args' => ['type' => BASE, 'tileId' => $this->id],
    ];
  }

  public function getUnitsModifier($n)
  {
    return -99;
  }
}
