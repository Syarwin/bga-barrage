<?php
namespace BRG\TechTiles;

use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Meeples;
use BRG\Core\Notifications;
use BRG\Map;

/*
 * Level 3 elevation
 */

class L3Elevation extends \BRG\TechTiles\BasicTile
{
  public function canConstruct($structure)
  {
    return $structure == \ELEVATION;
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
      $company->incScore(3 * count($byBasin));
      Notifications::score($company, 3 * count($byBasin), clienttranslate('(Level 3 Elevation advanced tile reward)'));
    }
    return null;
  }
}
