<?php
namespace BRG\Actions;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Officers;

class SpecialEffect extends \BRG\Models\Action
{
  public function getState()
  {
    return ST_SPECIAL_EFFECT;
  }

  protected function getObj()
  {
    $args = $this->getCtxArgs();
    if (isset($args['tileId'])) {
      $card = TechnologyTiles::get($args['tileId']);
    } elseif (isset($args['xoId'])) {
      $card = Officers::getInstance($args['xoId']);
    } else {
      return null;
    }

    return $card;
  }

  public function isDoable($company, $ignoreResources = false)
  {
    $args = $this->getCtxArgs();
    $card = $this->getObj();
    if (is_null($card)) {
      return false;
    }
    $method = 'is' . \ucfirst($args['method']) . 'Doable';
    $arguments = $args['args'] ?? [];
    return \method_exists($card, $method) ? $card->$method($company, $ignoreResources, ...$arguments) : true;
  }

  public function getDescription($ignoreResources = false)
  {
    $args = $this->getCtxArgs();
    $card = $this->getObj();
    if (is_null($card)) {
      return '';
    }
    $method = 'get' . \ucfirst($args['method']) . 'Description';
    $arguments = $args['args'] ?? [];
    return \method_exists($card, $method) ? $card->$method(...$arguments) : '';
  }

  public function isIndependent($player = null)
  {
    $args = $this->getCtxArgs();
    $card = $this->getObj();
    if (is_null($card)) {
      return false;
    }
    $method = 'isIndependent' . \ucfirst($args['method']);
    return \method_exists($card, $method) ? $card->$method($player) : false;
  }

  public function isAutomatic($player = null)
  {
    $args = $this->getCtxArgs();
    $card = $this->getObj();
    if (is_null($card)) {
      return false;
    }
    $method = $args['method'];
    return \method_exists($card, $method);
  }

  public function stSpecialEffect()
  {
    $args = $this->getCtxArgs();
    $card = $this->getObj();
    if (is_null($card)) {
      $this->resolveAction();
    }
    $method = $args['method'];
    $arguments = $args['args'] ?? [];
    if (\method_exists($card, $method)) {
      $card->$method(...$arguments);
      $this->resolveAction();
    }
  }

  public function argsSpecialEffect()
  {
    $args = $this->getCtxArgs();
    $card = $this->getObj();
    if (is_null($card)) {
      return [];
    }
    $method = 'args' . \ucfirst($args['method']);
    $arguments = $args['args'] ?? [];
    return \method_exists($card, $method) ? $card->$method(...$arguments) : [];
  }

  public function actSpecialEffect(...$actArgs)
  {
    $args = $this->getCtxArgs();
    $card = $this->getObj();
    if (is_null($card)) {
      $this->resolveAction();
    }
    $method = 'act' . \ucfirst($args['method']);
    $arguments = $args['args'] ?? [];
    if (!\method_exists($card, $method)) {
      throw new BgaVisibleSystemException('Corresponding act function does not exists : ' . $method);
    }

    $card->$method(...array_merge($actArgs, $arguments));
  }
}
