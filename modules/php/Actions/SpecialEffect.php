<?php
namespace BRG\Actions;
use BRG\Managers\PlayerCards;

class SpecialEffect extends \BRG\Models\Action
{
  public function getState()
  {
    return ST_SPECIAL_EFFECT;
  }

  public function isDoable($player, $ignoreResources = false)
  {
    $args = $this->getCtxArgs();
    $card = PlayerCards::get($args['cardId']);
    $method = 'is' . \ucfirst($args['method']) . 'Doable';
    $arguments = $args['args'] ?? [];
    return \method_exists($card, $method) ? $card->$method($player, $ignoreResources, ...$arguments) : true;
  }

  public function getDescription($ignoreResources = false)
  {
    $args = $this->getCtxArgs();
    $card = PlayerCards::get($args['cardId']);
    $method = 'get' . \ucfirst($args['method']) . 'Description';
    $arguments = $args['args'] ?? [];
    return \method_exists($card, $method) ? $card->$method(...$arguments) : '';
  }

  public function isIndependent($player = null)
  {
    $args = $this->getCtxArgs();
    $card = PlayerCards::get($args['cardId']);
    $method = 'isIndependent' . \ucfirst($args['method']);
    return \method_exists($card, $method) ? $card->$method($player) : false;
  }

  public function isAutomatic($player = null)
  {
    $args = $this->getCtxArgs();
    $card = PlayerCards::get($args['cardId']);
    $method = $args['method'];
    return \method_exists($card, $method);
  }

  public function stSpecialEffect()
  {
    $args = $this->getCtxArgs();
    $card = PlayerCards::get($args['cardId']);
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
    $card = PlayerCards::get($args['cardId']);
    $method = 'args' . \ucfirst($args['method']);
    $arguments = $args['args'] ?? [];
    return \method_exists($card, $method) ? $card->$method(...$arguments) : [];
  }

  public function actSpecialEffect(...$actArgs)
  {
    $args = $this->getCtxArgs();
    $card = PlayerCards::get($args['cardId']);
    $method = 'act' . \ucfirst($args['method']);
    $arguments = $args['args'] ?? [];
    if (!\method_exists($card, $method)) {
      throw new BgaVisibleSystemException('Corresponding act function does not exists : ' . $method);
    }

    $card->$method(...array_merge($actArgs, $arguments));
    $this->resolveAction();
  }
}
