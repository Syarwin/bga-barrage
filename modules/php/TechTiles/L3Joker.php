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

class L3Joker extends AdvancedTile
{
  protected $structureType = JOKER;
  protected $lvl = 3;
  public function getDescs()
  {
    $descs = parent::getDescs();
    $descs[] = clienttranslate(
      'When you use this tile, score 3 Victory Points for each Advanced Technology tile you acquired so far. Count all the Advanced Technology tiles in your personal supply and in your Construction Wheel.'
    );
    return $descs;
  }

  public function getPowerFlow($slot)
  {
    $company = Companies::getActive();
    $bonus = $company->countAdvancedTiles() * 3;

    if ($bonus > 0) {
      $company->incScore($bonus, clienttranslate('(Level 3 Joker advanced tile reward)'));
    }
    return null;
  }
}
