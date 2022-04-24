<?php
namespace BRG\Actions;

use BRG\Managers\ActionSpaces;
use BRG\Managers\Companies;
use BRG\Managers\Meeples;
use BRG\Core\Notifications;
use BRG\Core\Engine;
use BRG\Core\Globals;
use BRG\Core\Stats;
use BRG\Helpers\Utils;
use BRG\Helpers\Collection;

class PlaceEngineer extends \BRG\Models\Action
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->description = clienttranslate('Place an engineer');
  }

  public function getState()
  {
    return ST_PLACE_ENGINEER;
  }

  public function isDoable($company, $ignoreResources = false)
  {
    return $company->hasAvailableEngineer();
  }

  protected function getPlayableSpaces($company)
  {
    $availableEngineers = $company->countAvailableEngineers();
    $spaces = new Collection([]);
    foreach (ActionSpaces::getPlayableSpaces($company) as $space) {
      // Is there an engineer here ? (except Bank action space)
      if (($space['exclusive'] ?? true) && !Meeples::getOnSpace($space['uid'])->empty()) {
        continue;
      }

      // Do we have enough engineer in reserve ?
      if ($space['nEngineers'] > $availableEngineers) {
        continue;
      }

      // Handle cost
      if(($space['cost'] ?? 0) > 0){
        $flow = $space['flow']; // TODO
      }

      /*
      // Check that the action is doable
      $flow = $this->getFlow($player);
      $flowTree = Engine::buildTree($flow);
      return $flowTree->isDoable($company);
  */

      $spaces[$space['uid']] = $space;
    }

    return $spaces;
  }

  /**
   * Compute the selectable actions space for active compant
   */
  function argsPlaceEngineer()
  {
    $company = Companies::getActive();
    $spaces = self::getPlayableSpaces($company)->map(function ($space) use ($company) {
      $n = $space['nEngineers'];
      $choices = [$n];
      if ($n == 0) {
        // BANK
        $choices = range(1, $company->countAvailableEngineers());
      } elseif ($n == 1 && $company->isXO(XO_TOMMASO)) {
        $choices[] = N_ARCHITECT;
      }

      return $choices;
    });

    $args = [
      'spaces' => $spaces->toAssoc(),
    ];

    // TODO
    // $this->checkArgsModifiers($args, $player);

    return $args;
  }

  /**
   * Place the farmer on a card/space and activate the corresponding card
   *   to update the flow tree
   */
  function actPlaceEngineer($spaceId, $nEngineers)
  {
    self::checkAction('actPlaceEngineer');
    $args = self::argsPlaceEngineer();
    if (!array_key_exists($spaceId, $args['spaces'])) {
      throw new \BgaUserException('You cannot place an engineer here');
    }
    if (!in_array($nEngineers, $args['spaces'][$spaceId])) {
      throw new \BgaUserException('Invalid engineer number');
    }

    $company = Companies::getActive();
    $space = self::getPlayableSpaces($company)[$spaceId];

    /*
    $eventData = [
      'actionCardId' => $card->getId(),
      'actionCardType' => $card->getActionCardType(),
    ];
*/

    // Place engineer
    $board = ActionSpaces::getBoard($space['board']);
    $engineers = $company->placeEngineer($spaceId, $nEngineers);
    Notifications::placeEngineers($company, $engineers, $board);
    // TODO or not : Stats::incPlacedFarmers($player);

    // TODO : Are there cards triggered by the placement ?
    // $this->checkListeners('PlaceFarmer', $player, $eventData);

    // Activate action card
    $flow = $space['flow'];
    // TODO : tag flow tree ?
    // TODO : $this->checkModifiers('computePlaceFarmerFlow', $flow, 'flow', $player, $eventData);

    Engine::insertAsChild($flow);

    // TODO $this->checkAfterListeners($player, $eventData, false);
    $this->resolveAction(['spaceId' => $spaceId, 'n' => $nEngineers]);
  }
}
