<?php
namespace BRG\Models;
use BRG\Core\Engine;
use BRG\Core\Game;
use BRG\Core\Globals;
use BRG\Managers\Companies;

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

  public function getCompany()
  {
    $pId = $this->ctx->getCId() ?? Companies::getActiveId();
    return Companies::get($pId);
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
    if ($company->isAI()) {
      return; // Not using standard Engine for Automa
    }

    $args['automatic'] = $this->isAutomatic($company);
    Engine::resolveAction($args);
    Engine::proceed();
  }

  public static function checkAction($action, $byPassActiveCheck = false)
  {
    $company = Companies::getActive();
    if ($company->isAI()) {
      return true;
    }

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
}
