<?php
namespace BRG;
use BRG\Core\Globals;
use BRG\Managers\Players;
use BRG\Managers\Meeples;
use BRG\Managers\Fences;
use BRG\Managers\Actions;
use BRG\Managers\PlayerCards;
use BRG\Core\Engine;
use BRG\Core\Game;
use BRG\Models\PlayerBoard;
use BRG\Core\Notifications;
use BRG\Helpers\Utils;
use BRG\Map;

trait DebugTrait
{
  public function reloadColors()
  {
    $this->reloadPlayersBasicInfos();
  }

  public function tp()
  {
    $this->gamestate->jumpToState(\ST_BEFORE_START_OF_ROUND);
  }

  public function vt()
  {
    // $this->actTakeAtomicAction([['HC']]);
    // throw new \feException(print_r(Map::producingCapacity(5)));
    $this->actTakeAtomicAction(['C1L', 'B1L', 1]);
  }

  function addResource($type, $qty = 1)
  {
    if (!in_array($type, RESOURCES)) {
      throw new BgaVisibleSystemException("Didn't recognized the resource : " . $type);
    }

    $player = Players::getCurrent();
    $meeples = $player->createResourceInReserve($type, $qty);
    Notifications::gainResources($player, $meeples);
    Engine::proceed();
  }

  function infResources()
  {
    $player = Players::getCurrent();
    $meeples = [];
    foreach ([WOOD, CLAY, REED, STONE, FOOD] as $res) {
      $meeples = array_merge($meeples, $player->createResourceInReserve($res, 8));
    }
    Notifications::gainResources($player, $meeples);
    Engine::proceed();
  }

  function engSetup()
  {
    $pId = Players::getAll()->getIds()[0];

    Engine::setup([
      'childs' => [
        [
          'state' => ST_PLACE_FARMER,
          'pId' => $pId,
          'mandatory' => true,
        ],
      ],
    ]);
  }

  function engDisplay()
  {
    throw new \feException(print_r(Globals::getEngine()));
  }

  function engProceed()
  {
    Engine::proceed();
  }

  /*
   * loadBug: in studio, type loadBug(20762) into the table chat to load a bug report from production
   * client side JavaScript will fetch each URL below in sequence, then refresh the page
   */
  public function loadBug($reportId)
  {
    $db = explode('_', self::getUniqueValueFromDB("SELECT SUBSTRING_INDEX(DATABASE(), '_', -2)"));
    $game = $db[0];
    $tableId = $db[1];
    self::notifyAllPlayers(
      'loadBug',
      "Trying to load <a href='https://boardgamearena.com/bug?id=$reportId' target='_blank'>bug report $reportId</a>",
      [
        'urls' => [
          // Emulates "load bug report" in control panel
          "https://studio.boardgamearena.com/admin/studio/getSavedGameStateFromProduction.html?game=$game&report_id=$reportId&table_id=$tableId",

          // Emulates "load 1" at this table
          "https://studio.boardgamearena.com/table/table/loadSaveState.html?table=$tableId&state=1",

          // Calls the function below to update SQL
          "https://studio.boardgamearena.com/1/$game/$game/loadBugSQL.html?table=$tableId&report_id=$reportId",

          // Emulates "clear PHP cache" in control panel
          // Needed at the end because BGA is caching player info
          "https://studio.boardgamearena.com/admin/studio/clearGameserverPhpCache.html?game=$game",
        ],
      ]
    );
  }

  /*
   * loadBugSQL: in studio, this is one of the URLs triggered by loadBug() above
   */
  public function loadBugSQL($reportId)
  {
    $studioPlayer = self::getCurrentPlayerId();
    $players = self::getObjectListFromDb('SELECT player_id FROM player', true);

    // Change for your game
    // We are setting the current state to match the start of a player's turn if it's already game over
    $sql = ['UPDATE global SET global_value=2 WHERE global_id=1 AND global_value=99'];
    $sql[] = 'ALTER TABLE `gamelog` ADD `cancel` TINYINT(1) NOT NULL DEFAULT 0;';
    $map = [];
    foreach ($players as $pId) {
      $map[(int) $pId] = (int) $studioPlayer;

      // All games can keep this SQL
      $sql[] = "UPDATE player SET player_id=$studioPlayer WHERE player_id=$pId";
      $sql[] = "UPDATE global SET global_value=$studioPlayer WHERE global_value=$pId";
      $sql[] = "UPDATE stats SET stats_player_id=$studioPlayer WHERE stats_player_id=$pId";

      // Add game-specific SQL update the tables for your game
      $sql[] = "UPDATE meeples SET player_id=$studioPlayer WHERE player_id=$pId";
      $sql[] = "UPDATE cards SET player_id=$studioPlayer WHERE player_id=$pId";
      $sql[] = "UPDATE user_preferences SET player_id=$studioPlayer WHERE player_id=$pId";

      // This could be improved, it assumes you had sequential studio accounts before loading
      // e.g., quietmint0, quietmint1, quietmint2, etc. are at the table
      $studioPlayer++;
    }
    $msg =
      "<b>Loaded <a href='https://boardgamearena.com/bug?id=$reportId' target='_blank'>bug report $reportId</a></b><hr><ul><li>" .
      implode(';</li><li>', $sql) .
      ';</li></ul>';
    self::warn($msg);
    self::notifyAllPlayers('message', $msg, []);

    foreach ($sql as $q) {
      self::DbQuery($q);
    }

    /******************
     *** Fix Globals ***
     ******************/

    // Turn orders
    $turnOrders = Globals::getCustomTurnOrders();
    foreach ($turnOrders as $key => &$order) {
      $t = [];
      foreach ($order['order'] as $pId) {
        $t[] = $map[$pId];
      }
      $order['order'] = $t;
    }
    Globals::setCustomTurnOrders($turnOrders);

    // Engine
    $engine = Globals::getEngine();
    self::loadDebugUpdateEngine($engine, $map);
    Globals::setEngine($engine);

    // Skipped players
    $skippedPlayers = Globals::getSkippedPlayers();
    $t = [];
    foreach ($skippedPlayers as $pId) {
      $t[] = $map[$pId];
    }
    Globals::setSkippedPlayers($t);

    self::reloadPlayersBasicInfos();
  }

  function loadDebugUpdateEngine(&$node, $map)
  {
    if (isset($node['pId'])) {
      $node['pId'] = $map[(int) $node['pId']];
    }

    if (isset($node['childs'])) {
      foreach ($node['childs'] as &$child) {
        self::loadDebugUpdateEngine($child, $map);
      }
    }
  }

  /********************************
   ********* COMBO CHECKER *********
   ********************************/
  public function checkCombos()
  {
    $this->gamestate->jumpToState(\ST_CHECK_COMBOS);
  }

  public function getArgsCheckCombos($methodName)
  {
    // Load list of cards
    include dirname(__FILE__) . '/Cards/list.inc.php';
    $cards = [];
    foreach ($cardIds as $cId) {
      $card = PlayerCards::getCardInstance($cId);
      if (\method_exists($card, 'onPlayer' . $methodName)) {
        $cards[$cId] = $card;
      }
    }

    // Compute a specific ordering if needed
    $order = [];
    $edges = [];
    $orderName = 'order' . $methodName;
    foreach ($cards as $cId => $card) {
      if (\method_exists($card, $orderName)) {
        foreach ($card->$orderName() as $constraint) {
          $cId2 = $constraint[1];
          $op = $constraint[0];

          if (isset($order[$cId][$cId2]) && $order[$cId][$cId2] != $op) {
            throw new \feException('Incompatible ordering on following cards :' . $cId . ' ' . $cId2);
          }
          $order[$cId][$cId2] = $op;

          // Add the symmetric constraint
          $symOp = $op == '<' ? '>' : '<';
          if (isset($order[$cId2][$cId]) && $order[$cId2][$cId] != $symOp) {
            throw new \feException('Incompatible ordering on following cards :' . $cId . ' ' . $cId2);
          }
          $order[$cId2][$cId] = $symOp;

          // Add the edge
          $edges[] = [$op == '<' ? $cId : $cId2, $op == '<' ? $cId2 : $cId];
        }
      }
    }
    $nodes = array_keys($cards);
    $topoOrder = Utils::topological_sort($nodes, $edges);
    // Check if compute ordering respect every constaint
    if (true) {
      for ($i = 0; $i < count($cards); $i++) {
        for ($j = $i + 1; $j < count($cards); $j++) {
          $cId = $topoOrder[$i];
          $cId2 = $topoOrder[$j];
          if (isset($order[$cId][$cId2]) && $order[$cId][$cId2] != '<') {
            throw new \feException('Incompatible ordering after closure on following cards :' . $cId . ' ' . $cId2);
          }
        }
      }
    }

    $orderedCards = [];
    foreach ($topoOrder as $cId) {
      $orderedCards[] = $cards[$cId];
    }

    return [
      'cards' => $orderedCards,
      'order' => $order,
    ];
  }

  public function argsCheckCombos()
  {
    return [
      'construct' => $this->getArgsCheckCombos('ComputeCostsConstruct'),
      'renovate' => $this->getArgsCheckCombos('ComputeCostsRenovation'),
    ];
  }
}
