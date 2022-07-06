<?php
namespace BRG\TechTiles;

use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Meeples;
use BRG\Map;
use BRG\Helpers\Utils;

/*
 * Level 2 joker
 */

class L2Joker extends AdvancedTile
{
  protected $structureType = JOKER;
  public function getDescs()
  {
    $descs = parent::getDescs();
    $descs[] = clienttranslate(
      'When you use this tile to build a structure, you can use Excavators instead of Concrete Mixers and vice-versa, in any combination.'
    );
    return $descs;
  }

  public function applyConstructCostModifier(&$costs, $slot)
  {
    Utils::addCost($costs['costs'], [MIXER => 1, 'nb' => 1]);
    Utils::addCost($costs['costs'], [EXCAVATOR => 1, 'nb' => 1]);
  }
}
