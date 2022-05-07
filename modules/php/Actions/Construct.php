<?php
namespace BRG\Actions;
use BRG\Managers\Meeples;
use BRG\Managers\Players;
use BRG\Managers\Companies;
use BRG\Core\Notifications;
use BRG\Core\Stats;
use BRG\Helpers\Utils;
use BRG\Map;

class Construct extends \BRG\Models\Action
{
  protected $costMap = [
    BASE => ['type' => EXCAVATOR, MOUNTAIN => 5, HILL => 4, PLAIN => 3],
    ELEVATION => ['type' => MIXER, MOUNTAIN => 4, HILL => 3, PLAIN => 2],
    \POWERHOUSE => ['type' => MIXER],
    CONDUIT => ['type' => \EXCAVATOR],
  ];
  public function getState()
  {
    return \ST_CONSTRUCT;
  }

  public function stConstruct()
  {
    //not needed
  }

  public function argsConstruct()
  {
    $company = Companies::getActive();
    $resource = $company->getAllReserveResources();
    $possibilities = [BASE => [], \ELEVATION => [], \POWERHOUSE => [], CONDUIT => []];
    // get type of structure available

    foreach ([BASE, \ELEVATION, \POWERHOUSE, \CONDUIT] as $t) {
      if ($company->canConstruct($t)) {
        $m = Meeples::getTopOfType($t, $company->getId(), 'company');

        switch ($t) {
          case BASE:
            // check if there are some free slots
            foreach (Map::getBasins() as $bId => $basin) {
              if (
                // Basin is free
                Meeples::getFilteredQuery(null, $bId, [BASE])->count() == 0 &&
                // we have enough money (if needed)
                $resource[CREDIT] >= $basin['cost'] &&
                // and enough machines
                $resource[$this->costMap[$t]['type']] >= $this->costMap[$t][$basin['area']]
              ) {
                $possibilities[BASE][] = [
                  'meeple' => $m['id'],
                  'type' => $t,
                  'creditCost' => $basin['cost'],
                  'mType' => $this->costMap[$t]['type'],
                  'mCost' => $this->costMap[$t][$basin['area']],
                  'target' => $bId,
                ];
              }
            }
            break;
          case \ELEVATION:
            foreach (Map::getBasins() as $bId => $basin) {
              if (
                // there is already a base or elevation of this company
                Meeples::getFilteredQuery($company->getId(), $bId, [BASE, \ELEVATION])->count() > 0 &&
                // but not more than the maximum
                Meeples::getFilteredQuery($company->getId(), $bId, [BASE, \ELEVATION])->count() < 4 &&
                // company has enough money (if needed)
                $resource[CREDIT] >= $basin['cost'] &&
                // and enough machines
                $resource[$this->costMap[$t]['type']] >= $this->costMap[$t][$basin['area']]
              ) {
                $possibilities[\ELEVATION][] = [
                  'meeple' => $m['id'],
                  'type' => $t,
                  'creditCost' => $basin['cost'],
                  'mType' => $this->costMap[$t]['type'],
                  'mCost' => $this->costMap[$t][$basin['area']],
                  'target' => $bId,
                ];
              }
            }
            break;
          case \POWERHOUSE:
            foreach (Map::getPowerhouses() as $pId => $power) {
              if (
                // there are no powerhouse in this slot
                Meeples::getFilteredQuery(null, $pId, [\POWERHOUSE])->count() == 0 &&
                // company has enough money (if needed)
                $resource[CREDIT] >= $power['cost'] &&
                // and enough machines
                $resource[$this->costMap[$t]['type']] >=
                  (2 + Meeples::getFilteredQuery($company->getId(), null, [\POWERHOUSE])->count() == 0)
              ) {
                $possibilities[\POWERHOUSE][] = [
                  'meeple' => $m['id'],
                  'type' => $t,
                  'creditCost' => $power['cost'],
                  'mType' => $this->costMap[$t]['type'],
                  'mCost' => 2 + Meeples::getFilteredQuery($company->getId(), null, [\POWERHOUSE])->count() == 0,
                  'target' => $pId,
                ];
              }
            }
            break;
          case \CONDUIT:
            foreach (Map::getConduits() as $cId => $conduit) {
              if (
                // there are no conduit in this slot
                Meeples::getFilteredQuery(null, $cId, [\CONDUIT])->count() == 0 &&
                // and enough machines
                $resource[$this->costMap[$t]['type']] >= 2 * $conduit['production']
              ) {
                $possibilities[\CONDUIT][] = [
                  'meeple' => $m['id'],
                  'type' => $t,
                  'creditCost' => 0,
                  'mType' => $this->costMap[$t]['type'],
                  'mType' => 2 * $conduit['production'],
                  'target' => $cId,
                ];
              }
            }
            break;
        }
      }
    }
    return $possibilities;
  }
}
