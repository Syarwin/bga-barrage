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

  public function argsPlaceStructure()
  {
    $args = $this->getCtxArgs();
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
    if (isset($args['spaceId'])) {
      $spaces[] = $args['spaceId'];
    } else {
      foreach (Map::getConstructSlots() as $slot) {
        if ($slot['type'] != $args['type']) {
          continue;
        }

        // if we have a constraing of placement for base / elevation
        if (!is_null($constraints) && !in_array($slot['area'], $constraints)) {
          continue;
        }

        // TODO : Add other check like area and conduit production

        $spaces[] = $slot['id'];
      }
    }

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
    $args = $this->argsPlaceStructure();
    if (!in_array($spaceId, $args['spaces'])) {
      throw new \BgaUserException('You can\'t build here');
    }

    // Take top meeple and slide it
    $type = $this->getCtxArgs()['type'];
    $company = Companies::getActive();
    $mId = Meeples::getTopOfType($type, $company->getId(), 'company')['id'];
    Meeples::move($mId, $spaceId);
    Notifications::placeStructure($company, $type, $spaceId, Meeples::get($mId));

    $this->resolveAction([$spaceId]);
  }
}
