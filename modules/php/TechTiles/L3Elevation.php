<?php
namespace BRG\TechTiles;

use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Meeples;
use BRG\Core\Notifications;
use BRG\Core\Stats;
use BRG\Map;

/*
 * Level 3 elevation
 */

class L3Elevation extends AdvancedTile
{
  protected $structureType = ELEVATION;
  protected $lvl = 3;
  public function getDescs()
  {
    $descs = parent::getDescs();
    $descs[] = clienttranslate(
      'When you use this tile, score 3 Victory Points for each of your Dams that have at least one Elevation on them (all your level 2 and level 3 Dams).'
    );
    return $descs;
  }

  public function getPowerFlow($slot)
  {
    $company = Companies::getActive();

    // counting structure by basin
    $byBasin = [];
    foreach ($company->getBuiltStructures([BASE, \ELEVATION]) as $mId => $meeple) {
      if (!isset($byBasin[$meeple['location']])) {
        $byBasin[$meeple['location']] = 1;
      } else {
        $byBasin[$meeple['location']]++;
      }
    }

    $byBasin = array_filter($byBasin, function ($b) {
      if ($b >= 2) {
        return true;
      }
      return false;
    });

    if (count($byBasin) > 0) {
      $bonus = 3 * count($byBasin);
      $company->incScore($bonus, clienttranslate('(Level 3 Elevation advanced tile reward)'));
      Stats::incVpAdvancedTile($company, $bonus);
    }
    return null;
  }
}
