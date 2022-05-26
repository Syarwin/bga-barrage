<?php
namespace BRG\Actions;
use BRG\Managers\Meeples;
use BRG\Managers\Players;
use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Core\Notifications;
use BRG\Core\Engine;
use BRG\Core\Stats;
use BRG\Helpers\Utils;
use BRG\Map;

class Construct extends \BRG\Models\Action
{
  public function getState()
  {
    return \ST_CONSTRUCT;
  }

  public function getConstructablePairs($company)
  {
    $pairs = [];
    $tiles = $company->getAvailableTechTiles();
    foreach (Map::getConstructSlots() as $slot) {
      foreach ($tiles as $tile) {
        if (!$tile->canConstruct($slot['type'])) {
          continue;
        }

        // Construct the flow
        $childs = [];

        // 2] 3] Move tech tile
        $cost = $company->getConstructCost($slot, $tile);
        $cost['target'] = 'wheel';
        $cost['tileId'] = $tile->getId();
        $childs[] = [
          'action' => PAY,
          'args' => $cost,
        ];
        // 4] Rotate the wheel
        $childs[] = [
          'action' => ROTATE_WHEEL,
          'args' => ['n' => 1],
        ];
        // 5] Place the structure
        $childs[] = [
          'action' => PLACE_STRUCTURE,
          'args' => [
            'spaceId' => $slot['id'],
            'type' => $slot['type'],
          ],
        ];
        // 5bis] (OPT) Slot cost
        // moved in place structure
        // if (($slot['cost'] ?? 0) > 0) {
        //   $childs[] = [
        //     'action' => PAY,
        //     'args' => [
        //       'nb' => $slot['cost'],
        //       'costs' => Utils::formatCost([CREDIT => 1]),
        //       'source' => clienttranslate('building space'),
        //     ],
        //   ];
        // }

        // Construct the flow
        $flow = [
          'type' => NODE_SEQ,
          'childs' => $childs,
        ];

        // TODO : add isDoable
        if (true) {
          $pairs[] = [
            'spaceId' => $slot['id'],
            'tileId' => $tile->getId(),
            'flow' => $flow,
          ];
        }
      }
    }

    return $pairs;
  }

  public function argsConstruct()
  {
    $company = Companies::getActive();
    $pairs = self::getConstructablePairs($company);
    // Aggregate by space and clear flow
    $spaces = [];
    foreach ($pairs as &$pair) {
      $spaces[$pair['spaceId']][] = $pair['tileId'];
    }

    return ['spaces' => $spaces];
    /*
    die('test');

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
                Meeples::getFilteredQuery($company->getId(), $bId, [BASE, \ELEVATION])->count() < 3 &&
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
                  'mCost' => 2 * $conduit['production'],
                  'target' => $cId,
                ];
              }
            }
            break;
        }
      }
    }
    return ['possibilities' => $possibilities];
*/
  }

  public function actConstruct($spaceId, $tileId)
  {
    // Sanity checks
    self::checkAction('actConstruct');
    $company = Companies::getActive();
    $pairs = self::getConstructablePairs($company);
    Utils::filter($pairs, function ($pair) use ($spaceId, $tileId) {
      return $pair['spaceId'] == $spaceId && $pair['tileId'] == $tileId;
    });
    if (count($pairs) != 1) {
      throw new \BgaVisibleSystemException('Invalid combination on construct. Should not happen');
    }
    $pair = array_pop($pairs);

    // Insert the flow as a child and proceed
    Engine::insertAsChild($pair['flow']);
    $this->resolveAction(['space' => $spaceId, 'tile' => $tileId]);

    /*
    // move cost on wheel
    $movedResources = [];
    if ($resources != null) {
      foreach ($resources as $resource => $amount) {
        $movedResources = array_merge($movedResources, $company->placeOnWheel($resource, $amount));
      }
    } else {
      $movedResources = $company->placeOnWheel($filter['mType'], $filter['mCost']);
    }
    if (count($movedResources) != $filter['mCost']) {
      throw new \BgaVisibleSystemException('Not enough resource placed on wheel during construct. Should not happen');
    }

    // move tech tile
    $oTechnologyTiles = Meeples::getFilteredQuery($company->getId(), 'company', [$technologyTlle])
      ->get()
      ->first();
    if (is_null($oTechnologyTiles)) {
      throw new \BgaVisibleSystemException('Unavailable technology tile. Should not happen');
    }
    $techMoved = TechnologyTiles::move($oTechnologyTiles['id'], 'wheel', $company->getSlot());

    // move meeple on location
    $movedTarget = Meeples::move($meeple, $target);

    Notifications::construct(
      $company,
      $type,
      Meeples::get($movedTarget)['location'],
      Meeples::getMany($movedResources),
      $techMoved
    );

    // rotate wheel
    Engine::insertAsChild([
      'action' => ROTATE_WHEEL,
      'args' => [
        'n' => 1,
      ],
    ]);

    // insert bonus revenue if there is any
    if ($type != \POWERHOUSE) {
      $nb = Meeples::getFilteredQuery($company->getId(), null, $type)
        ->whereNotIn('meeple_location', ['company'])
        ->count();
      $bonus = $company->getRevenueBoard()[$type][$nb] ?? null;
      if ($bonus !== null) {
        Notifications::message(clienttranslate('A new revenue has been revealed. A bonus will be earned'));
        $bonus['source'] = clienttranslate('board revenue');
        Engine::insertAsChild($bonus);
      }
    }

    $this->resolveAction([$filter]);
*/
  }
}
