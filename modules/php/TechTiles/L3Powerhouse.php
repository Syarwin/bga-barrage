<?php
namespace BRG\TechTiles;

use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Meeples;
use BRG\Core\Notifications;
use BRG\Map;

/*
 * Level 3 powerhouse
 */

class L3Powerhouse extends \BRG\TechTiles\BasicTile
{
  public function canConstruct($structure)
  {
    return $structure == \POWERHOUSE;
  }

  public function getPowerFlow($slot)
  {
    $company = Companies::getActive();
    $bonus = $company->countBuiltStructures(POWERHOUSE) * 3;

    if ($bonus > 0) {
      $company->incScore($bonus);
      Notifications::score($company, $bonus, clienttranslate('(Level 3 Powerhouse advanced tile reward)'));
    }
    return null;
  }
}
