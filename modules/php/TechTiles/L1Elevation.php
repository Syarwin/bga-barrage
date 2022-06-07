<?php
namespace BRG\TechTiles;

use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Meeples;
use BRG\Map;

/*
 * Level 1 elevation
 */

class L1Elevation extends \BRG\TechTiles\BasicTile
{
  public function canConstruct($structure)
  {
    return $structure == \ELEVATION;
  }

  public function getPowerFlow($slot)
  {
    $company = Companies::get($this->cId);
    $rotate = count($company->getBuiltStructures(ELEVATION));

    if ($rotate > 0) {
      return ['action' => ROTATE_WHEEL, 'args' => ['n' => $rotate]];
    }
  }
}
