<?php
namespace BRG\Core;
use BRG\Managers\Players;
use BRG\Helpers\Utils;
use BRG\Core\Globals;

class Notifications
{
  /*************************
   **** GENERIC METHODS ****
   *************************/
  protected static function notifyAll($name, $msg, $data)
  {
    self::updateArgs($data);
    Game::get()->notifyAllPlayers($name, $msg, $data);
  }

  protected static function notify($player, $name, $msg, $data)
  {
    $pId = is_int($player) ? $player : $player->getId();
    self::updateArgs($data);
    Game::get()->notifyPlayer($pId, $name, $msg, $data);
  }

  public static function message($txt, $args = [])
  {
    self::notifyAll('message', $txt, $args);
  }

  public static function messageTo($player, $txt, $args = [])
  {
    $pId = is_int($player) ? $player : $player->getId();
    self::notify($pId, 'message', $txt, $args);
  }

  public static function clearTurn($player, $notifIds)
  {
    self::notifyAll('clearTurn', clienttranslate('${player_name} restart their turn'), [
      'player' => $player,
      'notifIds' => $notifIds,
    ]);
  }

  public static function refreshUI($datas)
  {
    // Keep only the thing that matters
    $fDatas = [
      'meeples' => $datas['meeples'],
      'players' => $datas['players'],
      //      'scores' => $datas['scores'],
    ];

    self::notifyAll('refreshUI', '', [
      'datas' => $fDatas,
    ]);
  }

  public static function refreshHand($player, $hand)
  {
    foreach ($hand as &$card) {
      $card = self::filterCardDatas($card);
    }
    self::notify($player, 'refreshHand', '', [
      'player' => $player,
      'hand' => $hand,
    ]);
  }

  public static function startNewRound($round)
  {
    self::notifyAll('startNewRound', clienttranslate('Starting round n°${round}'), [
      'round' => $round,
    ]);
  }

  public static function placeEngineers($company, $engineers, $board)
  {
    $msg = clienttranslate('${company_name} places ${n} engineers on board ${board}');
    self::notifyAll('placeEngineers', $msg, [
      'i18n' => ['board'],
      'company' => $company,
      'n' => $engineers->count(),
      'engineers' => $engineers->toArray(),
      'board' => $board::getName(),
    ]);
  }

  public static function payResources($company, $resources, $source, $cardSources = [], $cardNames = [])
  {
    $data = [
      'i18n' => ['source'],
      'company' => $company,
      'resources' => $resources,
      'source' => $source,
    ];
    $msg = clienttranslate('${company_name} pays ${resources_desc} for ${source}');

    // Card sources modifiers
    if (!empty($cardSources)) {
      die('TODO NOTIF PAY');
    }

    self::notifyAll('payResources', $msg, $data);
  }

  public static function gainResources($company, $meeples, $spaceId = null, $source = null)
  {
    if ($source != null) {
      $msg = clienttranslate('${company_name} gains ${resources_desc} (${source})');
    } else {
      $msg = clienttranslate('${company_name} gains ${resources_desc}');
    }

    self::notifyAll('gainResources', $msg, [
      'i18n' => ['source'],
      'company' => $company,
      'resources' => $meeples,
      'spaceId' => $spaceId,
      'source' => $source,
    ]);
  }

  /*********************
   **** UPDATE ARGS ****
   *********************/
  /*
   * Automatically adds some standard field about player and/or card
   */
  protected static function updateArgs(&$data)
  {
    if (isset($data['resource'])) {
      $names = [
        WOOD => clienttranslate('wood'),
        CLAY => clienttranslate('clay'),
        REED => clienttranslate('reed'),
        STONE => clienttranslate('stone'),
        GRAIN => clienttranslate('grain'),
        VEGETABLE => clienttranslate('vegetable'),
        SHEEP => clienttranslate('sheep'),
        PIG => clienttranslate('pig'),
        CATTLE => clienttranslate('cattle'),
        FOOD => clienttranslate('food'),
      ];

      $data['resource_name'] = $names[$data['resource']];
      $data['i18n'][] = 'resource_name';
    }

    if (isset($data['player'])) {
      $data['player_name'] = $data['player']->getName();
      $data['player_id'] = $data['player']->getId();
      unset($data['player']);
    }

    if (isset($data['company'])) {
      $data['company_name'] = $data['company']->getName();
      $data['company_id'] = $data['company']->getId();
      unset($data['company']);
    }

    if (isset($data['resources'])) {
      // Get an associative array $resource => $amount
      $resources = Utils::reduceResources($data['resources']);
      $data['resources_desc'] = Utils::resourcesToStr($resources);
    }
  }
}

?>
