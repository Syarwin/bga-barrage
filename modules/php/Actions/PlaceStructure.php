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

  public function getAvailableSpaces($company, $ignoreResources = false)
  {
    $args = $this->getCtxArgs();
    $credit = $company->countReserveResource(CREDIT);
    $constraints = $args['constraints'] ?? null;

    $spaces = [];
    foreach (Map::getConstructSlots() as $space) {
      if ($space['type'] != $args['type']) {
        continue;
      }

      // if we have a constraint on AREA placement for base / elevation
      if (!is_null($constraints) && !in_array($space['area'], $constraints)) {
        continue;
      }

      // TODO : Add other check like conduit production

      // Check that the elevation is on a base owned by the company
      // and that elevation + base is not more than 3
      $nStructures = $company->getStructures($space['id'])->count();
      if ($space['type'] == ELEVATION && ($nStructures == 0 || $nStructures == 3)) {
        continue;
      }

      // Check that the player can afford the cost
      if (!$ignoreResources && ($space['cost'] ?? 0) > $credit) {
        continue;
      }

      $spaces[$space['id']] = $space;
    }

    // If we have received a space in parameter, if it's defined we keep it, else return empty
    $spaceId = $args['spaceId'] ?? null;
    if (!is_null($spaceId)) {
      $spaces = isset($spaces[$spaceId]) ? [$spaceId => $spaces[$spaceId]] : [];
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
    $company = Companies::getActive();
    $spaces = $this->getAvailableSpaces($company);
    $space = $spaces[$spaceId] ?? null;
    if (is_null($space)) {
      throw new \BgaUserException('You can\'t build here');
    }

    // Take top meeple and slide it
    $type = $this->getCtxArgs()['type'];
    $mId = Meeples::getTopOfType($type, $company->getId(), 'company')['id'];
    Meeples::move($mId, $spaceId);
    Notifications::placeStructure($company, $type, $spaceId, Meeples::get($mId));

    if (($space['cost'] ?? 0) > 0) {
      Engine::insertAsChild([
        'action' => PAY,
        'args' => [
          'nb' => $space['cost'],
          'costs' => Utils::formatCost([CREDIT => 1]),
          'source' => clienttranslate('building space'),
        ],
      ]);
    }

    $this->resolveAction([$spaceId]);
  }
}
