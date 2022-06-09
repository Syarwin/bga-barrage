<?php
namespace BRG\TechTiles;

use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Meeples;
use BRG\Core\Notifications;
use BRG\Map;

/*
 * Level 3 joker
 */

class L3Joker extends \BRG\TechTiles\BasicTile
{
  public function canConstruct($structure)
  {
    return true;
  }

  public function getPowerFlow($slot)
  {
    $company = Companies::getActive();
    $bonus = $company->countAdvancedTiles() * 3;

    if ($bonus > 0) {
      $company->incScore($bonus);
      Notifications::score($company, $bonus, clienttranslate('(Level 3 Joker advanced tile reward)'));
    }
    return null;
  }
}
