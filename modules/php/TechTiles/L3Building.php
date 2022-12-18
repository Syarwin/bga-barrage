<?php
namespace BRG\TechTiles;
use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Buildings;
use BRG\Managers\Meeples;
use BRG\Core\Notifications;
use BRG\Core\Stats;
use BRG\Map;

/*
 * Level 3 building advanced tile
 */

class L3Building extends AdvancedTile
{
  protected $structureType = BUILDING;
  protected $lvl = 3;
  public function getDescs()
  {
    $descs = parent::getDescs();
    $descs[] = clienttranslate(
      'When you use this tile, immediately score the Victory Points awarded by the Private Building you have just activated. You will score them again at the end of the game, as usual.'
    );
    return $descs;
  }

  public function getPowerFlow($slot)
  {
    $id = (int) explode('-', $slot['id'])[1];
    $building = Buildings::getSingle($id);

    $bonus = $building->getVp();
    $company = Companies::getActive();
    $company->incScore($bonus, clienttranslate('(Level 3 Building advanced tile reward)'));
    Stats::incVpAdvancedTile($company, $bonus);
    return null;
  }
}
