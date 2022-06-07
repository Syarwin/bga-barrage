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
    $args = $this->getCtxArgs();
    $constraintType = $args['type'] ?? null;
    $constraintTile = $args['tileId'] ?? null;
    foreach (Map::getConstructSlots() as $slot) {
      foreach ($tiles as $tile) {
        if (!$tile->canConstruct($slot['type'])) {
          continue;
        }

        // constraints from advanced tiles
        if (!is_null($constraintTile) && $tile->getId() != $constraintTile) {
          continue;
        }

        if (!is_null($constraintType) && $constraintType != $slot['type']) {
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
            'tileId' => $tile->getId(),
          ],
        ];

        // 6] Manage bonus linked to the tile
        $childs[] = [
          'action' => \TILE_EFFECT,
          'args' => ['tileId' => $tile->getId(), 'slot' => $slot],
        ];

        // Construct the flow
        $flow = [
          'type' => NODE_SEQ,
          'childs' => $childs,
        ];

        $flowTree = Engine::buildTree($flow);
        if ($flowTree->isDoable($company)) {
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
  }

  public function actConstruct($spaceId, $tileId, $copiedTile = null)
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
    $tile = TechnologyTiles::get($tileId);
    if ($tile->getType() == \ANTON_TILE) {
      if (
        !in_array(
          $copiedTile,
          TechnologyTiles::getFilteredQuery($company->getId(), 'wheel')
            ->get()
            ->getIds()
        )
      ) {
        throw new \feException("You cannot copy this tile. it's not placed (anton power). Should not happen");
      }
      Globals::setAntonPower($copiedTile);
    }

    // Insert the flow as a child and proceed
    Engine::insertAsChild($pair['flow']);
    $this->resolveAction(['space' => $spaceId, 'tile' => $tileId]);
  }
}
