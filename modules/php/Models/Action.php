<?php
namespace BRG\Models;
use BRG\Core\Engine;
use BRG\Core\Game;
use BRG\Core\Globals;
use BRG\Managers\Company;

/*
 * Action: base class to handle atomic action
 */
class Action
{
  protected $ctx = null; // Contain ctx information : current node of flow tree
  protected $description = '';
  public function __construct($ctx)
  {
    $this->ctx = $ctx;
  }

  public function isDoable($company, $ignoreResources = false)
  {
    return true;
  }

  public function isOptional()
  {
    return false;
  }

  public function isIndependent($company = null)
  {
    return false;
  }

  public function isAutomatic($company = null)
  {
    return false;
  }

  public function getDescription($ignoreResources = false)
  {
    return $this->description;
  }

  public function getPlayer()
  {
    $pId = $this->ctx->getPId() ?? Players::getActiveId();
    return Players::get($pId);
  }

  public function getState()
  {
    return null;
  }

  public function isHarvest()
  {
    return Globals::isHarvest();
  }

  /**
   * Syntaxic sugar
   */
  public function resolveAction($args = [])
  {
    $company = Companies::getActive();
    $args['automatic'] = $this->isAutomatic($company);
    Engine::resolveAction($args);
    Engine::proceed();
  }

  public static function checkAction($action, $byPassActiveCheck = false)
  {
    if ($byPassActiveCheck) {
      Game::get()->gamestate->checkPossibleAction($action);
    } else {
      Game::get()->checkAction($action);
    }
  }

  public function getCtxArgs()
  {
    if ($this->ctx == null) {
      return [];
    }
    return $this->ctx->getArgs() ?? [];
  }

  public function getClassName()
  {
    $classname = get_class($this);
    if ($pos = strrpos($classname, '\\')) {
      return substr($classname, $pos + 1);
    }
    return $classname;
  }


/*
TODO : modifiers and listeners

  protected function checkListeners($method, $company, $args = [])
  {
    $event = array_merge(
      [
        'cId' => $company->getId(),
        'type' => 'action',
        'action' => $this->getClassName(),
        'method' => $method,
      ],
      $args
    );

    $reaction = PlayerCards::getReaction($event);
    if (!is_null($reaction)) {
      Engine::insertAsChild($reaction);
    }
  }

  public function checkAfterListeners($company, $args = [], $duringActionListener = true)
  {
    if ($duringActionListener) {
      $this->checkListeners($this->getClassName(), $company, $args);
    }
    $this->checkListeners('ImmediatelyAfter' . $this->getClassName(), $company, $args);
    $this->checkListeners('After' . $this->getClassName(), $company, $args);
  }

  public function checkModifiers($method, &$data, $name, $company, $args = [])
  {
    $args[$name] = $data;
    $args['actionCardId'] = $this->ctx != null ? $this->ctx->getCardId() : null;
    PlayerCards::applyEffects($company, $method, $args);
    $data = $args[$name];
  }

  public function checkCostModifiers(&$costs, $company, $args = [])
  {
    $this->checkModifiers('computeCosts' . $this->getClassName(), $costs, 'costs', $company, $args);
  }

  public function checkArgsModifiers(&$actionArgs, $company, $args = [])
  {
    $this->checkModifiers('computeArgs' . $this->getClassName(), $actionArgs, 'actionArgs', $company, $args);
  }
*/
}
