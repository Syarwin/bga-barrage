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
use BRG\Core\Game;

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
      if ($space['nEngineers'] > $availableEngineers && $space['nEngineers'] != INFTY) {
        continue;
      }

      $flow = $space['flow'];

      // Check that the action is doable
      $space['flow'] = self::tagTree($flow, $company->getId(), $space['uid']);
      $flowTree = Engine::buildTree($space['flow']);
      if ($flowTree->isDoable($company)) {
        $spaces[$space['uid']] = $space;
      }
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
        $choices = range(1, $company->countAvailableEngineers());
      }
      // TODO: XO_TOMMASO
      // elseif ($n == 1 && $company->isXO(XO_TOMMASO)) {
      //   $choices[] = N_ARCHITECT;
      // }
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
    $board = ActionSpaces::getBoard($space['board']);
    $engineers = $company->placeEngineer($spaceId, $nEngineers);
    Notifications::placeEngineers($company, $engineers, $board);

    // Activate action card
    $flow = $space['flow'];
    if ($space['uid'] == 'bank-b') {
      // Handle the bank
      $flow['args'] = [CREDIT => $nEngineers];
    }

    Engine::insertAsChild($flow);
    $this->resolveAction(['spaceId' => $spaceId, 'n' => $nEngineers]);
  }
}
