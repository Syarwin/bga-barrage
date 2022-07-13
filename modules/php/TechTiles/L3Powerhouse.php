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

class L3Powerhouse extends AdvancedTile
{
  protected $structureType = POWERHOUSE;
  public function getDescs()
  {
    $descs = parent::getDescs();
    $descs[] = clienttranslate(
      'When you use this tile, scre 3 Victory Points for each Powerhouse you have built. Count also the Powerhoure you have just built using this tile.'
    );
    return $descs;
  }

  public function getPowerFlow($slot)
  {
    $company = Companies::getActive();
    $bonus = $company->countBuiltStructures(POWERHOUSE) * 3;

    if ($bonus > 0) {
      $company->incScore($bonus, clienttranslate('(Level 3 Powerhouse advanced tile reward)'));
    }
    return null;
  }
}
