<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Barrage implementation : © Timothe Pecatte <tim.pecatte@gmail.com>, Vincent Toper <vincent.toper@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * barrage.game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 *
 */

$swdNamespaceAutoload = function ($class) {
  $classParts = explode('\\', $class);
  if ($classParts[0] == 'BRG') {
    array_shift($classParts);
    $file = dirname(__FILE__) . '/modules/php/' . implode(DIRECTORY_SEPARATOR, $classParts) . '.php';
    if (file_exists($file)) {
      require_once $file;
    } else {
      var_dump('Cannot find file : ' . $file);
    }
  }
};
spl_autoload_register($swdNamespaceAutoload, true, true);

require_once APP_GAMEMODULE_PATH . 'module/table/table.game.php';

use BRG\Core\Globals;
use BRG\Core\Engine;
use BRG\Core\Preferences;
use BRG\Core\Stats;
use BRG\Core\Notifications;
use BRG\Managers\Players;
use BRG\Managers\Companies;
use BRG\Managers\Officers;
use BRG\Managers\Meeples;
use BRG\Managers\Contracts;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\ActionSpaces;
use BRG\Managers\AutomaCards;
use BRG\Managers\ExternalWorks;
use BRG\Helpers\Log;
use BRG\Map;

class barrage extends Table
{
  use BRG\DebugTrait;
  use BRG\States\SetupTrait;
  use BRG\States\RoundStartTrait;
  use BRG\States\RoundEndTrait;
  use BRG\States\ActionTrait;
  use BRG\States\EngineTrait;
  use BRG\States\EndOfGameTrait;
  use BRG\States\BonusObjectiveTileTrait;
  use BRG\States\AutomaTurnTrait;
  use BRG\States\AutomaPlaceStructureTrait;

  public static $instance = null;
  function __construct()
  {
    parent::__construct();
    self::$instance = $this;
    self::initGameStateLabels([
      'logging' => 10,
    ]);

    Engine::boot();
    Map::init();
    Stats::checkExistence();
  }

  public static function get()
  {
    return self::$instance;
  }

  protected function getGameName()
  {
    return 'barrage';
  }

  public function getGameOptionValue($optionId)
  {
    $query = new BRG\Helpers\QueryBuilder('global', null, 'global_id');
    $val = $query
      ->where('global_id', $optionId)
      ->get()
      ->first();
    return is_null($val) ? null : $val['global_value'];
  }

  /*
   * getAllDatas:
   */
  public function getAllDatas()
  {
    $pId = self::getCurrentPId();
    $data = [
      'canceledNotifIds' => Log::getCanceledNotifIds(),
      'prefs' => Preferences::getUiData($pId),
      'players' => Players::getUiData($pId),
      'companies' => Companies::getUiData($pId),
      'nCompanies' => Companies::count(),
      'actionBoards' => ActionSpaces::getUiData(),
      'meeples' => Meeples::getUiData(),
      'bases' => Meeples::getSelectQuery()
        ->where('type', BASE)
        ->get()
        ->toArray(),
      'map' => Map::getUiData(),
      'bonusTiles' => Globals::getBonusTiles(),
      'objectiveTile' => Globals::getObjectiveTile(),
      'contracts' => Contracts::getUiData(),
      'techTiles' => TechnologyTiles::getUiData(),
      'round' => Globals::getRound(),
      'phase' => Globals::getPhase(),
      'bonuses' => $this->computeBonuses(),
      'automa' => AutomaCards::getUiData(),
      'works' => ExternalWorks::getUiData(),
      'LWP' => Globals::isLWP(),
    ];

    $XOIds = array_slice(Globals::getMahiriAddXO(), Companies::count() - 1);
    if (!empty($XOIds)) {
      $data['mahiriOfficers'] = [];
      foreach ($XOIds as $xId) {
        $data['mahiriOfficers'][] = Officers::getInstance($xId);
      }
    }

    return $data;
  }

  /*
   * getGameProgression:
   */
  function getGameProgression()
  {
    return (Globals::getRound() * 100) / 6;
  }

  function actChangePreference($pref, $value)
  {
    Preferences::set($this->getCurrentPId(), $pref, $value);
  }

  function getArgs()
  {
    return $this->gamestate->state()['args'];
  }

  function changePhase($str = '')
  {
    $currentPhase = Globals::getPhase();
    if ($currentPhase != $str) {
      Globals::setPhase($str);
      Notifications::changePhase($str);
    }
  }

  /////////////////////////////////////////////////////////////
  // Exposing protected methods, please use at your own risk //
  /////////////////////////////////////////////////////////////

  // Exposing protected method getCurrentPlayerId
  public static function getCurrentPId()
  {
    return self::getCurrentPlayerId();
  }

  // Exposing protected method translation
  public static function translate($text)
  {
    return self::_($text);
  }

  ///////////////////////////////////////////////
  ///////////////////////////////////////////////
  ////////////   Custom Turn Order   ////////////
  ///////////////////////////////////////////////
  ///////////////////////////////////////////////
  public function initCustomTurnOrder(
    $key,
    $callback,
    $endCallback,
    $loop = false,
    $autoNext = true,
    $args = [],
    $order = null
  ) {
    $turnOrders = Globals::getCustomTurnOrders();
    $turnOrders[$key] = [
      'order' => $order ?? Companies::getTurnOrder(),
      'index' => -1,
      'callback' => $callback,
      'args' => $args, // Useful mostly for auto card listeners
      'endCallback' => $endCallback,
      'loop' => $loop,
    ];
    Globals::setCustomTurnOrders($turnOrders);

    if ($autoNext) {
      $this->nextPlayerCustomOrder($key);
    }
  }

  public function initCustomDefaultTurnOrder($key, $callback, $endCallback, $loop = false, $autoNext = true)
  {
    $this->initCustomTurnOrder($key, $callback, $endCallback, $loop, $autoNext);
  }

  public function nextPlayerCustomOrder($key)
  {
    $turnOrders = Globals::getCustomTurnOrders();
    if (!isset($turnOrders[$key])) {
      throw new BgaVisibleSystemException(
        'Asking for the next player of a custom turn order not initialized : ' . $key
      );
    }

    // Increase index and save
    $o = $turnOrders[$key];
    $i = $o['index'] + 1;
    if ($i == count($o['order']) && $o['loop']) {
      $i = 0;
    }
    $turnOrders[$key]['index'] = $i;
    Globals::setCustomTurnOrders($turnOrders);

    if ($i < count($o['order'])) {
      $this->gamestate->jumpToState(ST_GENERIC_NEXT_PLAYER);
      Companies::changeActive($o['order'][$i]);
      $this->jumpToOrCall($o['callback'], $o['args']);
    } else {
      $this->endCustomOrder($key);
    }
  }

  public function endCustomOrder($key)
  {
    $turnOrders = Globals::getCustomTurnOrders();
    if (!isset($turnOrders[$key])) {
      throw new BgaVisibleSystemException('Asking for ending a custom turn order not initialized : ' . $key);
    }

    $o = $turnOrders[$key];
    $turnOrders[$key]['index'] = count($o['order']);
    Globals::setCustomTurnOrders($turnOrders);
    $callback = $o['endCallback'];
    $this->jumpToOrCall($callback);
  }

  public function jumpToOrCall($mixed, $args = [])
  {
    if (is_int($mixed) && array_key_exists($mixed, $this->gamestate->states)) {
      $this->gamestate->jumpToState($mixed);
    } elseif (method_exists($this, $mixed)) {
      $method = $mixed;
      $this->$method($args);
    } else {
      throw new BgaVisibleSystemException('Failing to jumpToOrCall  : ' . $mixed);
    }
  }

  /********************************************
   ******* GENERIC CARD LISTENERS CHECK ********
   ********************************************/
  /*
   * A lot of time you want to loop through all the player to see if a card react or not
   *  => this is achieved using custom turn order with an arg containing the eventType
   *  => the custom order will call the genericPlayerCheckListeners that will getReaction from cards if any
   */
  public function checkCardListeners($typeEvent, $endCallback, $event = [], $order = null)
  {
    $event['type'] = $typeEvent;
    $event['method'] = $typeEvent;
    $this->initCustomTurnOrder($typeEvent, $order, 'genericPlayerCheckListeners', $endCallback, false, true, $event);
  }

  function genericPlayerCheckListeners($event)
  {
    $pId = Players::getActiveId();
    $event['pId'] = $pId;
    $reaction = PlayerCards::getReaction($event);

    if (is_null($reaction)) {
      // No reaction => just go to next player
      $this->nextPlayerCustomOrder($event['type']);
    } else {
      // Reaction => boot up the Engine
      Engine::setup($reaction, ['order' => $event['type']]);
      Engine::proceed();
    }
  }

  ////////////////////////////////////
  ////////////   Zombie   ////////////
  ////////////////////////////////////
  /*
   * zombieTurn:
   *   This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
   *   You can do whatever you want in order to make sure the turn of this player ends appropriately
   */
  public function zombieTurn($state, $activePlayer)
  {
    $skipped = Globals::getSkippedCompanies();
    $cId = Globals::getActiveCompany();
    if (!in_array((int) $cId, $skipped)) {
      $skipped[] = (int) $cId;
      Globals::setSkippedCompanies($skipped);
    }

    $stateName = $state['name'];
    if ($state['type'] === 'activeplayer') {
      if ($stateName == 'confirmPartialTurn') {
        $this->actConfirmTurn();
      } elseif ($stateName == 'confirmPartialTurn') {
        $this->actConfirmPartialTurn();
      }
      // Clear all node of player
      elseif (Engine::getNextUnresolved() != null) {
        Engine::clearZombieNodes($cId);
        Engine::proceed();
      } else {
        $this->gamestate->nextState('zombiePass');
      }
    } elseif ($state['type'] === 'multipleactiveplayer') {
      // Make sure player is in a non blocking status for role turn
      $this->gamestate->setPlayerNonMultiactive($activePlayer, '');
    }
  }

  /////////////////////////////////////
  //////////   DB upgrade   ///////////
  /////////////////////////////////////
  // You don't have to care about this until your game has been published on BGA.
  // Once your game is on BGA, this method is called everytime the system detects a game running with your old Database scheme.
  // In this case, if you change your Database scheme, you just have to apply the needed changes in order to
  //   update the game database and allow the game to continue to run with your new version.
  /////////////////////////////////////
  /*
   * upgradeTableDb
   *  - int $from_version : current version of this game database, in numerical form.
   *      For example, if the game was running with a release of your game named "140430-1345", $from_version is equal to 1404301345
   */
  public function upgradeTableDb($from_version)
  {
    if ($from_version <= 2208122344) {
      $sql =
        'CREATE TABLE IF NOT EXISTS `works` (`work_id` int(10) unsigned NOT NULL, `work_location` varchar(32) NOT NULL, `work_state` int(10), PRIMARY KEY (`work_id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8';
      self::applyDbUpgradeToAllDB($sql);
    }
  }
}
