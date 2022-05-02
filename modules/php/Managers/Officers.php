<?php
namespace BRG\Managers;
use BRG\Core\Globals;
use BRG\Core\Stats;
use BRG\Helpers\Utils;

/*
 * Officers manager : allows to easily access players, including automas
 */
class Officers
{
  static $classes = [
    XO_WILHELM => 'Wilhelm',
    XO_ELON => 'Elon',
    XO_TOMMASO => 'Tommaso',
    XO_GRAZIANO => 'Graziano',
    XO_VIKTOR => 'Viktor',
    XO_MARGOT => 'Margot',
    XO_GENNARO => 'Gennaro',
    XO_SOLOMON => 'Solomon',
    XO_ANTON => 'Anton',
    XO_SIMONE => 'Simone',
    XO_JILL => 'Jill',
    XO_MAHIRI => 'Mahiri',
    XO_LESLIE => 'Leslie',
    XO_WU => 'Wu',
    XO_OCTAVIUS => 'Octavius',
    XO_AMIR => 'Amir',
  ];

  public function getInstance($xId, $company = null)
  {
    $className = '\BRG\Officers\\' . static::$classes[$xId];
    return new $className($company);
  }

  protected function getAvailable()
  {
    $officerIds = [];
    foreach (static::$classes as $xId => $className) {
      $officer = self::getInstance($xId);
      if ($officer->isAvailable()) {
        $officerIds[] = $xId;
      }
    }

    return $officerIds;
  }

  public function randomStartingPick($nPlayers)
  {
    $officerIds = self::getAvailable();
    return Utils::rand($officerIds, $nPlayers);
  }
}
