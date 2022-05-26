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

  public function stPlaceStructure()
  {
    $args = $this->argsPlaceStructure();
    if (count($args['spaces']) == 1) {
      $this->actPlaceStructure($args['spaces'][0]);
    }
  }

  public function argsPlaceStructure($action = false)
  {
    $args = $this->getCtxArgs();
    $company = Companies::getActive();
    $constraints = $args['constraints'] ?? null;
    // Type of structure
    $structureNames = [
      BASE => clienttranslate('Base'),
      ELEVATION => clienttranslate('Elevation'),
      POWERHOUSE => clienttranslate('Powerhouse'),
      CONDUIT => clienttranslate('Conduit'),
    ];

    // Spaces
    $spaces = [];
    $detailedSpaces = [];
    $credit = $company->countReserveResource(CREDIT);

    foreach (Map::getConstructSlots() as $slot) {
      if ($slot['type'] != $args['type']) {
        continue;
      }

      // if we have a constraint of placement for base / elevation
      if (!is_null($constraints) && !in_array($slot['area'], $constraints)) {
        continue;
      }

      // TODO : Add other check like conduit production
      // check that the elevation is on a base owned by the company
      if ($slot['type'] == ELEVATION && Meeples::getOnSpace($slot['id'], [BASE], $company->getId())->empty()) {
        continue;
      }

      // check that elevation + base is not more than 3
      if (
        $slot['type'] == ELEVATION &&
        count(Meeples::getOnSpace($slot['id'], [BASE, ELEVATION], $company->getId())) > 2
      ) {
        continue;
      }

      // can we pay the cost of placement if needed?
      if (($slot['cost'] ?? 0) > $credit) {
        continue;
      }
      if ($action) {
        $detailedSpaces[$slot['id']] = $slot;
      }
      $spaces[] = $slot['id'];
    }

    // if we have received a space in parameter, if it's defined we keep it, else return empty
    if (isset($args['spaceId']) && in_array($args['spaceId'], $spaces)) {
      $spaces = [$args['spaceId']];
    } else {
      throw new \BgaUserException(clienttranslate('You cannot build on this slot'));
      $spaces = [];
    }

    $data = [
      'i18n' => ['structure', 'location'],
      'structure' => $structureNames[$args['type']],
      'location' => $constraints,
      'spaces' => $spaces,
      'descSuffix' => count($spaces) == 1 ? 'auto' : (is_null($constraints) ? '' : 'constraints'),
    ];
    if ($action) {
      $data = array_merge($data, ['detailedSpaces' => $detailedSpaces]);
    }
    return $data;
  }

  public function actPlaceStructure($spaceId, $auto = false)
  {
    self::checkAction('actPlaceStructure', $auto);
    $args = $this->argsPlaceStructure(true);
    if (!in_array($spaceId, $args['spaces'])) {
      throw new \BgaUserException('You can\'t build here');
    }
    $slot = $args['detailedSpaces'][$spaceId];

    // Take top meeple and slide it
    $type = $this->getCtxArgs()['type'];
    $company = Companies::getActive();
    $mId = Meeples::getTopOfType($type, $company->getId(), 'company')['id'];
    Meeples::move($mId, $spaceId);
    Notifications::placeStructure($company, $type, $spaceId, Meeples::get($mId));

    if (($slot['cost'] ?? 0) > 0) {
      Engine::insertAsChild([
        'action' => PAY,
        'args' => [
          'nb' => $slot['cost'],
          'costs' => Utils::formatCost([CREDIT => 1]),
          'source' => clienttranslate('building space'),
        ],
      ]);
    }

    $this->resolveAction([$spaceId]);
  }
}
