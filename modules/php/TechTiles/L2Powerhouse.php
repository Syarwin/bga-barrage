<?php
namespace BRG\TechTiles;

use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Meeples;
use BRG\Map;

/*
 * Level 2 powerhouse
 */

class L2Powerhouse extends AdvancedTile
{
  protected $structureType = POWERHOUSE;
  protected $lvl = 2;
  public function getDescs()
  {
    $descs = parent::getDescs();
    $descs[] = clienttranslate(
      'When you use this tile, you can perform a production action with a bonus of +2. The production can be performed using any of your Powerhouse, it must be performed immediately and has no extra cost.'
    );
    return $descs;
  }

  public function getPowerFlow($slot)
  {
    return [
      'action' => PRODUCE,
      'optional' => true,
      'args' => [
        'bonus' => 2,
      ],
    ];
  }
}
