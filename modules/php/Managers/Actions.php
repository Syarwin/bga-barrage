<?php
namespace BRG\Managers;
use BRG\Core\Game;

/* Class to manage all the cards for Agricola */

class Actions
{
  static $classes = [
    PLACE_ENGINEER => 'PlaceEngineer',
    GAIN => 'Gain',
    CONSTRUCT => 'Construct',
    PAY => 'Pay',
    PRODUCT => 'Product',
    // FULFILL_CONTRACT => 'FulfillContract', // TODO
    PLACE_DROPLET => 'PlaceDroplet',
    ROTATE_WHEEL => 'RotateWheel',
    TAKE_CONTRACT => 'TakeContract',
    SPECIAL_EFFECT => 'SpecialEffect',
    DISCARD_CONTRACTS => 'DiscardContract',
  ];

  public static function get($actionId, $ctx = null)
  {
    if (!\array_key_exists($actionId, self::$classes)) {
      throw new \BgaVisibleSystemException('Trying to get an atomic action not defined in Actions.php : ' . $actionId);
    }
    $name = '\BRG\Actions\\' . self::$classes[$actionId];
    return new $name($ctx);
  }

  public static function getActionOfState($stateId, $throwErrorIfNone = true)
  {
    foreach (array_keys(self::$classes) as $actionId) {
      if (self::getState($actionId, null) == $stateId) {
        return $actionId;
      }
    }

    if ($throwErrorIfNone) {
      throw new \BgaVisibleSystemException('Trying to fetch args of a non-declared atomic action in state ' . $stateId);
    } else {
      return null;
    }
  }

  public static function isDoable($actionId, $ctx, $company, $ignoreResources = false)
  {
    $res = self::get($actionId, $ctx)->isDoable($company, $ignoreResources);
    return $res;

    // TODO
    // Cards that bypass isDoable (eg Paper Maker)
    // $args = [
    //   'action' => $actionId,
    //   'ignoreResources' => $ignoreResources,
    //   'isDoable' => $res,
    //   'ctx' => $ctx,
    // ];
    // PlayerCards::applyEffects($company, 'isDoable', $args);
    // return $args['isDoable'];
  }

  public static function getState($actionId, $ctx)
  {
    return self::get($actionId, $ctx)->getState();
  }

  public static function getArgs($actionId, $ctx)
  {
    $action = self::get($actionId, $ctx);
    $methodName = 'args' . self::$classes[$actionId];
    return array_merge($action->$methodName(), ['optionalAction' => $ctx->isOptional()]);
  }

  public static function takeAction($actionId, $args, $ctx)
  {
    $company = Companies::getActive();
    if (!self::isDoable($actionId, $ctx, $company)) {
      throw new \BgaUserException('Action not doable. Should not happen.' . $actionId);
    }

    $action = self::get($actionId, $ctx);
    $methodName = 'act' . self::$classes[$actionId];
    $action->$methodName(...$args);
  }

  public static function stAction($actionId, $ctx)
  {
    $company = Companies::getActive();
    if (!self::isDoable($actionId, $ctx, $company)) {
      if (!$ctx->isOptional()) {
        throw new \BgaUserException('Action not doable. Should not happen' . $actionId);
      } else {
        // Auto pass if optional and not doable
        Game::get()->actPassOptionalAction(true);
        return;
      }
    }

    $action = self::get($actionId, $ctx);
    $methodName = 'st' . self::$classes[$actionId];
    if (\method_exists($action, $methodName)) {
      $action->$methodName();
    }
  }
}
