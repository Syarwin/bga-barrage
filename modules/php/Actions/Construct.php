<?php
namespace BRG\Actions;
use BRG\Managers\Meeples;
use BRG\Managers\Players;
use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Core\Notifications;
use BRG\Core\Engine;
use BRG\Core\Stats;
use BRG\Core\Globals;
use BRG\Helpers\Utils;
use BRG\Map;

class Construct extends \BRG\Models\Action
{
  public function getState()
  {
    return \ST_CONSTRUCT;
  }

  public function isDoable($company, $ignoreResources = false)
  {
    return $this->getConstructablePairs($company, $ignoreResources, true);
  }

  public function getConstructablePairs($company, $ignoreResources = false, $checkingIsDoable = false, $args = null)
  {
    $args = $args ?? $this->getCtxArgs();
    // constraints from advanced tiles
    $constraintType = $args['type'] ?? null;
    $constraintTile = $args['tileId'] ?? null;
    $constraintsArea = $args['constraints'] ?? null; // FOR AUTOMA
    $tiles = $company->getAvailableTechTiles($constraintType, true);
    $antonTile = TechnologyTiles::getAnton();
    if (!is_null($constraintTile)) {
      $tiles = $tiles->filter(function ($tile) use ($constraintTile) {
        return $tile->getId() == $constraintTile;
      });
    }

    $pairs = [];
    foreach (Map::getConstructSlots() as $slot) {
      if (!is_null($constraintType) && $constraintType != $slot['type']) {
        continue;
      }

      // AUTOMA if we have a constraint on AREA placement for base / elevation
      if (!is_null($constraintsArea) && !in_array($space['area'], $constraintsArea)) {
        continue;
      }

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
        if ($tile->getLocation() == 'wheel') {
          unset($cost['tileId']);
          if (!is_null($antonTile) && $antonTile->getCId() == $company->getId()) {
            $cost['tileId'] = $antonTile->getId();
          }
        }
        $cost['source'] = clienttranslate('construction');
        if (!$ignoreResources) {
          $childs[] = [
            'action' => PAY,
            'args' => $cost,
          ];
        }
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
        if (!$company->isAI() || $company->getLvlAI() >= 2) {
          $childs[] = [
            'action' => \TILE_EFFECT,
            'args' => ['tileId' => $tile->getId(), 'slot' => $slot],
          ];
        }

        // Construct the flow
        $flow = [
          'type' => NODE_SEQ,
          'childs' => $childs,
        ];

        $flowTree = Engine::buildTree($flow);
        if ($flowTree->isDoable($company)) {
          if ($checkingIsDoable) {
            return true;
          }

          $pairs[] = [
            'spaceId' => $slot['id'],
            'tileId' => $tile->getId(),
            'tileStructureType' => $tile->getStructureType(),
            'tileLvl' => $tile->getLvl(),
            'type' => $slot['type'],
            'flow' => $flow,
          ];
        }
      }
    }

    if ($checkingIsDoable) {
      return false;
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
    $data = ['spaces' => $spaces];

    // Handle Anton
    $tile = TechnologyTiles::getAnton();
    if (!is_null($tile)) {
      $copied = Globals::getAntonPower();
      $data['antonId'] = $tile->getId();
      $data['antonPower'] = $company->isAntonTileAvailable() ? $company->getWheelTiles()->getIds() : [];
      $data['antonCopied'] = $copied == '' ? null : $copied;
    }

    return $data;
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

    // Handle Anton
    if ($tile->getType() == \ANTON_TILE && Globals::getAntonPower() == '') {
      // prettier-ignore
      if (!$company->getWheelTiles()->getIds()->includes($copiedTile)) {
        throw new \feException("You cannot copy this tile. it's not placed (Anton power). Should not happen");
      }
      if (!TechnologyTiles::get($copiedTile)->canConstruct($pair['type'])) {
        throw new \BgaVisibleSystemException(
          clienttranslate('You cannot construct with this tile. Please select a valid one')
        );
      }

      Globals::setAntonPower($copiedTile);
      // Insert the flow as a child and proceed
      Engine::insertAsChild($pair['flow']);
    } else {
      // Insert the flow as a child and proceed
      Engine::insertAsChild($pair['flow']);
    }
    $this->resolveAction(['space' => $spaceId, 'tile' => $tileId]);
  }
}
