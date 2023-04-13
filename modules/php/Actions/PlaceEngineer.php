<?php
namespace BRG\Actions;

use BRG\Managers\ActionSpaces;
use BRG\Managers\Companies;
use BRG\Managers\Meeples;
use BRG\Managers\Contracts;
use BRG\Core\Notifications;
use BRG\Core\Engine;
use BRG\Core\Globals;
use BRG\Core\Stats;
use BRG\Core\Game;
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
    return $company->hasAvailableEngineer() || $company->hasEngineerFreeTiles();
  }

  protected function getPlayableSpaces($company)
  {
    $credit = $company->countReserveResource(CREDIT);
    $availableEngineers = $company->countAvailableEngineers();
    $spaces = new Collection([]);
    foreach (ActionSpaces::getPlayableSpaces($company) as $space) {
      // Is there an engineer here ? (except Bank action space)
      if (($space['exclusive'] ?? true) && !Meeples::getOnSpace($space['uid'])->empty()) {
        continue;
      }

      // Do we have enough engineer in reserve ?
      if ($space['nEngineers'] > $availableEngineers && $space['nEngineers'] != INFTY) {
        continue;
      }
      if ($space['nEngineers'] == 0 && $availableEngineers == 0) {
        continue; // Special case of the bank
      }

      $flow = $space['flow'];

      // Check that the action is doable
      $cost = $space['cost'] ?? 0;
      if ($cost > $credit) {
        continue;
      }

      $space['flow'] = self::tagTree($flow, $company->getId(), $space['uid']);
      $flowTree = Engine::buildTree($space['flow']);
      $company->setTmpReducedCredit($cost);
      if ($flowTree->isDoable($company)) {
        $spaces[$space['uid']] = $space;
      }
      $company->setTmpReducedCredit(0);
    }

    return $spaces;
  }

  /**
   * Tag all the subtree flow with the information about the space so we can access it in the ctx later
   */
  protected function tagTree($t, $cId, $spaceId)
  {
    $t['spaceId'] = $spaceId;
    $t['cId'] = $cId;
    if (isset($t['childs'])) {
      $t['childs'] = array_map(function ($child) use ($cId, $spaceId) {
        return self::tagTree($child, $cId, $spaceId);
      }, $t['childs']);
    }
    return $t;
  }

  /**
   * Compute the selectable actions space for active compant
   */
  function argsPlaceEngineer()
  {
    $company = Companies::getActive();
    $spaces = self::getPlayableSpaces($company);
    $mahiri = null;
    $choices = $spaces->map(function ($space) use ($company, &$mahiri) {
      $n = $space['nEngineers'];
      $choices = [$n];
      if ($n == 0) {
        // BANK
        $m = $company->countAvailableEngineers();
        $choices = $m == 0 ? [] : range(1, $m);
      }
      if ($n <= 1 && $company->isXO(XO_TOMMASO) && $company->hasAvailableArchitect()) {
        $choices[] = N_ARCHITECT;
      }
      if (($space['flow']['action'] ?? null) == \EXTERNAL_WORK && $company->isLeslieTileAvailable()) {
        $choices[] = \LESLIE_TILE;
      }

      if (is_null($mahiri) && stripos($space['uid'], 'mahiri') !== false) {
        $mahiri = $space['uid'];
      }

      return $choices;
    });

    // Compute construct spaces to add buttons
    $constructSpaces = $spaces
      ->filter(function ($space) {
        return $space['construct'] ?? false;
      })
      ->getIds();

    // Add alternative actions
    $alternativeActions = [];
    foreach ($company->getEngineerFreeTiles() as $tile) {
      $tile->addAlternativeActions($alternativeActions);
    }
    Utils::filter($alternativeActions, function ($action) use ($company) {
      $tree = Engine::buildTree($action['flow']);
      return $tree->isDoable($company, null, false);
    });

    $args = [
      'spaces' => $choices->toAssoc(),
      'constructSpaces' => $constructSpaces,
      'mahiri' => $mahiri,
      'alternativeActions' => $alternativeActions,
      'canSkip' => !$company->hasAvailableEngineer(),
    ];

    return $args;
  }

  function stPlaceEngineer()
  {
    // Check whether contracts need to be filled up again or not
    if (Contracts::needRefill()) {
      $contracts = Contracts::refillStacks();
      if (!$contracts->empty()) {
        Notifications::refillStacks($contracts, true);
      }
    }

    $args = $this->argsPlaceEngineer();
    if (empty($args['spaces']) && empty($args['alternativeActions'])) {
      Game::get()->actSkip(true);
    }
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

    // Place engineer
    $leslie = false;
    if ($nEngineers == \LESLIE_TILE) {
      $leslie = true;
      $nEngineers = 2;
    }

    $board = ActionSpaces::getBoard($space['board']);
    $engineers = $company->placeEngineer($spaceId, $nEngineers);
    Notifications::placeEngineers($company, $engineers, $board);

    // Activate action card
    $flow = $space['flow'];
    if ($space['uid'] == 'bank-b') {
      // Handle the bank ( < 0 for ARCHITECT)
      $flow['args'] = [CREDIT => $nEngineers < 0 ? 1 : $nEngineers];
    }
    if ($leslie) {
      $flow['args']['leslie'] = true;
    }

    // Handle cost
    if (($space['cost'] ?? 0) > 0) {
      $flow = [
        'type' => NODE_SEQ,
        'childs' => [
          [
            'action' => PAY,
            'args' => [
              'nb' => 1,
              'costs' => Utils::formatCost([CREDIT => $space['cost']]),
              'source' => clienttranslate('Action Space Cost'),
            ],
          ],
          $flow,
        ],
      ];
    }

    // ARCHITECT
    if ($nEngineers == -1) {
      $flow = [
        'type' => NODE_SEQ,
        'childs' => [
          $flow,
          [
            'action' => \PLACE_ENGINEER,
            'optional' => true,
          ],
        ],
      ];
    }

    Engine::insertAsChild($flow);
    $this->resolveAction(['spaceId' => $spaceId, 'n' => $nEngineers]);
  }
}
