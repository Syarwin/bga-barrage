<?php
namespace BRG\TechTiles;

use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Meeples;
use BRG\Map;

/*
 * Level 2 base
 */

class L2Base extends AdvancedTile
{
  protected $structureType = BASE;
  protected $lvl = 2;
  public function getDescs()
  {
    $descs = parent::getDescs();
    $descs[] = clienttranslate(
      'When you use this tile, produce a number of Energy Units equal to the number of Bases you have built. Count also the Base you have just built.'
    );
    $descs[] = clienttranslate(
      'The energy produced is recorded on the Energy Track and can be used to fulfill a Contract.'
    );
    return $descs;
  }

  public function getPowerFlow($slot)
  {
    $company = Companies::get($this->cId);
    $energy = $company->countBuiltStructures(BASE);

    if ($energy > 0) {
      return [
        'type' => NODE_SEQ,
        'childs' => [
          ['action' => GAIN, 'args' => [ENERGY => $energy]],
          [
            'action' => \FULFILL_CONTRACT,
            'optional' => true,
            'args' => [ENERGY => $energy],
          ],
        ],
      ];
    }
    return null;
  }
}
