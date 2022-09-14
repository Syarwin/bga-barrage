<?php
namespace BRG\TechTiles;

/*
 * Level 1 elevation
 */

class L1Elevation extends AdvancedTile
{
  protected $structureType = ELEVATION;
  protected $lvl = 1;
  public function getDescs()
  {
    $descs = parent::getDescs();
    $descs[] = clienttranslate(
      'When you use this tile, rotate your Construction Wheel by 1 segment for every Elevation that you have built. Count also the Elevation you have just built using this tile.'
    );
    return $descs;
  }

  public function getPowerFlow($slot)
  {
    $company = $this->getCompany();
    $rotate = count($company->getBuiltStructures(ELEVATION));

    if ($rotate > 0) {
      return ['action' => ROTATE_WHEEL, 'args' => ['n' => $rotate]];
    }
  }
}
