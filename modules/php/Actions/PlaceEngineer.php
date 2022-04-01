<?php
namespace BRG\Actions;

use BRG\Managers\Players;
use BRG\Managers\Meeples;
use BRG\Core\Notifications;
use BRG\Core\Engine;
use BRG\Core\Globals;
use BRG\Core\Stats;

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

  public function isDoable($player, $ignoreResources = false)
  {
    return $player->hasFarmerAvailable();
  }

  /**
   * Compute the selectable actions cards/space for active player
   */
  function argsPlaceFarmer()
  {
    $player = Players::getActive();
    $cards = ActionCards::getVisible($player);
    $constraints = $this->getCtxArgs()['constraints'] ?? null;

    $args = [
      'allCards' => $cards->getIds(),
      'cards' => $cards
        ->filter(function ($card) use ($player) {
          return $card->canBePlayed($player);
        })
        ->getIds(),
    ];

    $this->checkArgsModifiers($args, $player);

    if ($constraints != null) {
      $args['cards'] = \array_values(\array_intersect($constraints, $args['cards']));
    }
    return $args;
  }

  /**
   * Place the farmer on a card/space and activate the corresponding card
   *   to update the flow tree
   */
  function actPlaceFarmer($cardId)
  {
    die("todo");

    /*
    self::checkAction('actPlaceFarmer');
    $player = Players::getActive();

    $cards = self::argsPlaceFarmer()['cards'];
    if (!\in_array($cardId, $cards)) {
      throw new \BgaUserException(clienttranslate('You cannot place a person here'));
    }

    if (in_array($cardId, $player->getActionCards()->getIds())) {
      $card = PlayerCards::get($cardId);
    } else {
      $card = ActionCards::get($cardId);
    }

    $eventData = [
      'actionCardId' => $card->getId(),
      'actionCardType' => $card->getActionCardType(),
    ];

    // Place farmer
    $fId = $player->moveNextFarmerAvailable($cardId);
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
    */
  }
}
