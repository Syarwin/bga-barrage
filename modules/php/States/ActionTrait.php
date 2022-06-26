<?php
namespace BRG\States;
use BRG\Core\Globals;
use BRG\Managers\Players;
use BRG\Managers\Companies;
use BRG\Managers\Meeples;
use BRG\Managers\Fences;
use BRG\Managers\Actions;
use BRG\Core\Engine;
use BRG\Core\Game;
use BRG\Models\PlayerBoard;
use BRG\Core\Notifications;

trait ActionTrait
{
  /**
   * Trying to get the atomic action corresponding to the state where the game is
   */
  function getCurrentAtomicAction()
  {
    $stateId = $this->gamestate->state_id();
    return Actions::getActionOfState($stateId);
  }

  /**
   * Ask the corresponding atomic action for its args
   */
  function argsAtomicAction()
  {
    $company = Companies::getActive();
    $action = $this->getCurrentAtomicAction();
    $node = Engine::getNextUnresolved();
    $args = Actions::getArgs($action, $node);
    $args['automaticAction'] = Actions::get($action, $node)->isAutomatic($company);
    $args['previousEngineChoices'] = Globals::getEngineChoices();
    $this->addArgsAnytimeAction($args, $action);

    return $args;
  }

  /**
   * Add anytime actions
   */
  function addArgsAnytimeAction(&$args, $action)
  {
    // If the action is auto => don't display anytime buttons
    if ($args['automaticAction'] ?? false) {
      return;
    }
    $company = Companies::getActive();

    $anytimeActions = [];
    $args['alternativeActions'] = array_merge($args['alternativeActions'] ?? [], $anytimeActions);
  }

  function actAlternativeAction($choiceId)
  {
    $args = $this->gamestate->state()['args'];
    if (!isset($args['alternativeActions'][$choiceId])) {
      throw new \BgaVisibleSystemException('You can\'t take this alternative action');
    }

    $action = $args['alternativeActions'][$choiceId];
    $flow = $action['flow'];
    Globals::incEngineChoices();

    // Insert the flow as child or at root depending on the 'resolve' flag
    if($action['resolve'] ?? true){
      Engine::insertAsChild($flow, false);
      Engine::resolveAction(['alternativeAction' => $choiceId]);
    } else {
      Engine::insertAtRoot($flow, false);
    }

    Engine::proceed();
  }

  /**
   * Pass the argument of the action to the atomic action
   */
  function actTakeAtomicAction($args)
  {
    $action = $this->getCurrentAtomicAction();
    Actions::takeAction($action, $args, Engine::getNextUnresolved());
  }

  /**
   * To pass if the action is an optional one
   *
   */
  function actPassOptionalAction($auto = false)
  {
    if ($auto) {
      $this->gamestate->checkPossibleAction('actPassOptionalAction');
    } else {
      self::checkAction('actPassOptionalAction');
    }

    if (!Engine::getNextUnresolved()->isOptional()) {
      self::error(Engine::getNextUnresolved()->toArray());
      throw new \BgaVisibleSystemException('This action is not optional');
    }

    Engine::resolve(PASS);
    Engine::proceed();
  }

  /**
   * Pass the argument of the action to the atomic action
   */
  function stAtomicAction()
  {
    $action = $this->getCurrentAtomicAction();
    Actions::stAction($action, Engine::getNextUnresolved());
  }
}
