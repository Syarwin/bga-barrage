<?php
namespace BRG\Actions;
use BRG\Managers\Meeples;
use BRG\Managers\Players;
use BRG\Managers\Companies;
use BRG\Managers\TechnologyTiles;
use BRG\Core\Notifications;
use BRG\Core\Engine;
use BRG\Core\Stats;
use BRG\Core\Game;
use BRG\Helpers\Utils;
use BRG\Helpers\FlowConvertor;
use BRG\Map;

class PlaceStructure extends \BRG\Models\Action
{
  public function getState()
  {
    return ST_PLACE_STRUCTURE;
  }

  public function isDoable($company, $ignoreResources = true)
  {
    $spaces = $this->getAvailableSpaces($company, $ignoreResources);
    return !empty($spaces);
  }

  public function isAutomatic($company = null)
  {
    $args = $this->argsPlaceStructure();
    return count($args['spaces']) == 1;
  }

  public function stPlaceStructure()
  {
    $args = $this->argsPlaceStructure();
    if (count($args['spaces']) == 1) {
      $this->actPlaceStructure($args['spaces'][0]);
    }
  }

  public function getAvailableSpaces($company, $ignoreResources = false, $args = null)
  {
    $args = $args ?? $this->getCtxArgs();
    $credit = $company->countReserveResource(CREDIT);
    $constraints = $args['constraints'] ?? null;

    $spaces = [];
    // If we have received a space in parameter, if it's defined we keep it, else return empty
    $possibleSpaces = Map::getConstructSlots();
    $types = $company->getAvailableStructureTypes();
    if (!is_null($args['spaceId'] ?? null)) {
      $possibleSpaces = [$args['spaceId'] => $possibleSpaces[$args['spaceId']]];
    }

    foreach ($possibleSpaces as $space) {
      $ignoreMalus = false;
      if ($space['type'] != $args['type']) {
        continue;
      }

      // Do we have any structure left of this type ?
      if (!in_array($space['type'], $types)) {
        continue;
      }

      // if we have a constraint on AREA placement for base / elevation
      if (!is_null($constraints) && !in_array($space['area'], $constraints)) {
        continue;
      }

      // If constraints on conduit, continue if it products more
      if ($args['type'] == CONDUIT && isset($args['n']) && $space['production'] > $args['n']) {
        continue;
      }

      // Check that the elevation is on a base owned by the company
      // and that elevation + base is not more than 3
      $nStructures = count(Map::getBuiltStructures($space['id'], $company));
      if ($space['type'] == ELEVATION && ($nStructures == 0 || $nStructures == 3)) {
        continue;
      }

      // cannot place if the spot is taken
      if (in_array($space['type'], [POWERHOUSE, CONDUIT]) && Map::getBuiltStructure($space['id']) != null) {
        continue;
      }

      // same player cannot have 2 powerhouse in the same zone
      if ($space['type'] == \POWERHOUSE && count(Map::getBuiltPowerhousesInZone($space['zone'], $company)) > 0) {
        continue;
      }

      // same player cannot have 2 bases on the same basin
      if ($space['type'] == BASE && count(Map::getBuiltDamsInZone($space['zone'], $company)) > 0) {
        continue;
      }

      if (isset($args['tileId']) && TechnologyTiles::get($args['tileId'])->ignoreCostMalus()) {
        $ignoreMalus = true;
      } elseif ($company->isAntonTileAvailable()) {
        foreach (TechnologyTiles::getFilteredQuery($company->getId(), 'wheel')->get() as $tileId => $tile) {
          if ($tile->canConstruct($space['type']) && $tile->ignoreCostMalus()) {
            $ignoreMalus = true;
            break;
          }
        }
      }

      // Check that the player can afford the cost
      $cost = $args['type'] == ELEVATION ? 0 : ($space['cost'] ?? 0); // No need to pay to place an elevation on a red spot
      if (!$ignoreResources && !$ignoreMalus && $cost > $credit) {
        continue;
      }

      $spaces[$space['id']] = $space;
    }

    return $spaces;
  }

  public function argsPlaceStructure($action = false)
  {
    $args = $this->getCtxArgs();
    $company = Companies::getActive();
    $spaces = array_keys($this->getAvailableSpaces($company));
    $constraints = $args['constraints'] ?? null;

    // Type of structure
    $structureNames = [
      BASE => clienttranslate('Base'),
      ELEVATION => clienttranslate('Elevation'),
      POWERHOUSE => clienttranslate('Powerhouse'),
      CONDUIT => clienttranslate('Conduit'),
    ];

    return [
      'i18n' => ['structure', 'location'],
      'structure' => $structureNames[$args['type']],
      'location' => $constraints,
      'spaces' => $spaces,
      'descSuffix' => count($spaces) == 1 ? 'auto' : (is_null($constraints) ? '' : 'constraints'),
    ];
  }

  public function actPlaceStructure($spaceId, $auto = false)
  {
    self::checkAction('actPlaceStructure', $auto);
    $args = $this->getCtxArgs();
    $company = Companies::getActive();
    $spaces = $this->getAvailableSpaces($company);
    $space = $spaces[$spaceId] ?? null;
    if (is_null($space)) {
      throw new \BgaUserException('You can\'t build here');
    }

    $type = $this->getCtxArgs()['type'];
    $cost = $type == ELEVATION ? 0 : ($space['cost'] ?? 0); // No need to pay to place an elevation on a red spot
    $this->placeStructure($spaceId, $type, $cost, $args['tileId'] ?? null);
    $this->resolveAction([$spaceId]);
  }

  public function placeStructure($spaceId, $type, $cost = 0, $tileId = null)
  {
    $company = Companies::getActive();
    $isAI = $company->isAI();

    // Take top meeple and slide it
    $meeple = Meeples::getTopOfType($type, $company->getId(), 'company');
    $mId = $meeple['id'];
    Meeples::insertOnTop($mId, $spaceId);
    Notifications::placeStructure($company, $type, $spaceId, Meeples::get($mId));
    Map::placeStructure($meeple, $spaceId);

    // Increase stat
    $statName = 'inc' . ucfirst($type);
    Stats::$statName($company, 1);

    if ($cost > 0 && (is_null($tileId) || !TechnologyTiles::get($tileId)->ignoreCostMalus())) {
      $flow = [
        'action' => PAY,
        'args' => [
          'nb' => $cost,
          'costs' => Utils::formatCost([CREDIT => 1]),
          'source' => clienttranslate('building space'),
        ],
      ];

      Engine::insertAsChild($flow, $isAI);
    }

    if (!$company->isAI() || $company->getLvlAI() > 0) {
      // insert bonus revenue if there is any
      $nb = $company->countBuiltStructures($type);
      $bonus = $company->getBoardIncomes()[$type][$nb] ?? null;
      if ($bonus !== null && $type != POWERHOUSE) {
        Notifications::newIncomeRevealed($company);
        $flow = FlowConvertor::computeRewardFlow($bonus, clienttranslate('board revenue'), $isAI);
        $vp = FlowConvertor::getVp($bonus);
        Stats::incVpStructures($company, $vp);

        if ($isAI) {
          $actions = Game::get()->convertFlowToAutomaActions($flow);
          Game::get()->automaTakeActions($actions);
        } else {
          Engine::insertAsChild($flow, $isAI);
        }
      }
    }
  }
}
