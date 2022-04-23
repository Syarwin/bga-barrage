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
    $spaces = ActionSpaces::getPlayableSpaces($company);
    $availableEngineers = $company->countAvailableEngineers();

    Utils::filter($spaces, function ($space) use ($availableEngineers) {
      // Is there an engineer here ? (except Bank action space)
      if (($space['exclusive'] ?? true) && !Meeples::getOnSpace($space['uid'])->empty()) {
        return false;
      }

      // Do we have enough engineer in reserve ?
      if ($space['nEngineers'] > $availableEngineers) {
        return false;
      }

      /*
      // Check that the action is doable
      $flow = $this->getFlow($player);
      $flowTree = Engine::buildTree($flow);
      return $flowTree->isDoable($player);
  */

      return true;
    });

    // foreach($spaces as $space){
    //
    // }

    return $spaces;
  }

  /**
   * Compute the selectable actions space for active compant
   */
  function argsPlaceEngineer()
  {
    $company = Companies::getActive();
    $spaces = [];
    foreach (self::getPlayableSpaces($company) as $space) {
      $n = $space['nEngineers'];
      $choices = [$n];
      if ($n == 0) {
        // BANK
        $choices = range(1, $company->countAvailableEngineers());
      } elseif ($n == 1 && $company->isXO(XO_TOMMASO)) {
        $choices[] = N_ARCHITECT;
      }

      $spaces[$space['uid']] = $choices;
    }

    $args = [
      'spaces' => $spaces,
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
    $company = Companies::getActive();

    die('todo');

    $spaceIds = self::argsPlaceEngineer()['spaceIds'];
    if (!\in_array($spaceId, $spaceIds)) {
      throw new \BgaUserException(clienttranslate('You cannot place an engineer here'));
    }

    if (in_array($cardId, $player->getActionCards()->getIds())) {
      $card = PlayerCards::get($cardId);
    } else {
      $card = ActionCards::get($cardId);
    }

    /*
    $eventData = [
      'actionCardId' => $card->getId(),
      'actionCardType' => $card->getActionCardType(),
    ];
*/

    // Place engineer
    $engineerIds = $player->moveNextFarmerAvailable($cardId);
    Notifications::placeFarmer($player, $fId, $card, $this->ctx->getSource());
    Stats::incPlacedFarmers($player);

    // Are there cards triggered by the placement ?
    $this->checkListeners('PlaceFarmer', $player, $eventData);

    // Activate action card
    $flow = $card->getFlow($player);
    $this->checkModifiers('computePlaceFarmerFlow', $flow, 'flow', $player, $eventData);

    // D101 side effect
    if (!$card->hasAccumulation() && Meeples::getResourcesOnCard($cardId)->count() > 0) {
      $flow = [
        'type' => NODE_SEQ,
        'childs' => [
          [
            'action' => COLLECT,
            'cardId' => $cardId,
          ],
          $flow,
        ],
      ];
    }
    Engine::insertAsChild($flow);

    $this->checkAfterListeners($player, $eventData, false);
    $this->resolveAction(['actionCardId' => $cardId]);
  }
}
