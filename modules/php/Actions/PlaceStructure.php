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

  public function stPlaceStructure()
  {
    $args = $this->argsPlaceStructure();
    if (count($args['spaces']) == 1) {
      $this->actPlaceStructure($args['spaces'][0]);
    }
  }

  public function getAvailableSpaces($company, $ignoreResources = false, $args = null)
  {
    $args = $this->getCtxArgs();
    $credit = $company->countReserveResource(CREDIT);
    $constraints = $args['constraints'] ?? null;

    $spaces = [];
    // If we have received a space in parameter, if it's defined we keep it, else return empty
    $possibleSpaces = Map::getConstructSlots();
    if (!is_null($args['spaceId'] ?? null)) {
      $possibleSpaces = [$args['spaceId'] => $possibleSpaces[$args['spaceId']]];
    }

    foreach ($possibleSpaces as $space) {
      $ignoreMalus = false;
      if ($space['type'] != $args['type']) {
        continue;
      }

      // Do we have any structure left of this type ?
      $meeple = Meeples::getTopOfType($space['type'], $company->getId(), 'company');
      if (is_null($meeple)) {
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
      $nStructures = $company->getStructures($space['id'])->count();
      if ($space['type'] == ELEVATION && ($nStructures == 0 || $nStructures == 3)) {
        continue;
      }

      // cannot place if the spot is taken
      if (in_array($space['type'], [POWERHOUSE, CONDUIT]) && Meeples::getOnSpace($space['id'])->count() > 0) {
        continue;
      }

      // same player cannot have 2 powerhouse in the same zone
      if (
        $space['type'] == \POWERHOUSE &&
        count($company->getBuiltStructures(\POWERHOUSE, 'P' . $space['zone'] . '%')) > 0
      ) {
        continue;
      }

      // same player cannot have 2 bases on the same basin
      if (
        $space['type'] == BASE &&
        count(
          $company->getBuiltStructures(\BASE, [substr($space['id'], 0, -1) . 'L', substr($space['id'], 0, -1) . 'U'])
        ) > 0
      ) {
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
      if (!$ignoreResources && !$ignoreMalus && ($space['cost'] ?? 0) > $credit) {
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

    // Take top meeple and slide it
    $type = $this->getCtxArgs()['type'];
    $mId = Meeples::getTopOfType($type, $company->getId(), 'company')['id'];
    Meeples::insertOnTop($mId, $spaceId);
    Notifications::placeStructure($company, $type, $spaceId, Meeples::get($mId));

    if (
      ($space['cost'] ?? 0) > 0 &&
      (!isset($args['tileId']) || !TechnologyTiles::get($args['tileId'])->ignoreCostMalus())
    ) {
      Engine::insertAsChild([
        'action' => PAY,
        'args' => [
          'nb' => $space['cost'],
          'costs' => Utils::formatCost([CREDIT => 1]),
          'source' => clienttranslate('building space'),
        ],
      ]);
    }

    // insert bonus revenue if there is any
    $nb = $company->countBuiltStructures($type);
    $bonus = $company->getBoardIncomes()[$type][$nb] ?? null;
    if ($bonus !== null) {
      if ($type != POWERHOUSE) {
        Notifications::newIncomeRevealed($company);
        $flow = FlowConvertor::computeRewardFlow($bonus, clienttranslate('board revenue'));
        Engine::insertAsChild($flow);
      }
    }

    $this->resolveAction([$spaceId]);
  }
}
