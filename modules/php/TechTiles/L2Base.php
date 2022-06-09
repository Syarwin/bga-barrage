<?php
namespace BRG\TechTiles;

use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Meeples;
use BRG\Map;

/*
 * Level 2 base
 */

class L2Base extends \BRG\TechTiles\BasicTile
{
  public function canConstruct($structure)
  {
    return $structure == BASE;
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
