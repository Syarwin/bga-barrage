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

  public static function assignCompany($player, $company)
  {
    self::notifyAll('assignCompany', clienttranslate('${player_name} picks ${company_name}'), [
      'player' => $player,
      'company_name' => $company->getCname(),
      'company_id' => $company->getId(),
      'datas' => $company,
    ]);
  }

  public static function setupCompanies($meeples, $tiles)
  {
    self::notifyAll('setupCompanies', '', [
      'meeples' => $meeples->toArray(),
      'tiles' => $tiles->toArray(),
    ]);
  }

  public static function pickContracts($company, $contracts)
  {
    self::notifyAll('pickContracts', clienttranslate('${company_name} picks ${nb} contract(s)'), [
      'company' => $company,
      'contracts' => $contracts,
      'nb' => count($contracts),
    ]);
  }

  public static function startNewRound($round)
  {
    self::notifyAll('startNewRound', clienttranslate('Starting round n°${round}'), [
      'round' => $round,
    ]);
  }

  public static function fillHeadstreams($meeples)
  {
    self::notifyAll('gainResources', clienttranslate('Filling headstreams'), [
      'resources' => $meeples,
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

  public static function silentDestroy($resources)
  {
    self::notifyAll('silentDestroy', '', [
      'resources' => $resources,
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

  public static function payResourcesTo(
    $company,
    $resources,
    $source,
    $cardSources = [],
    $cardNames = [],
    $otherCompany
  ) {
    $data = [
      'i18n' => ['source'],
      'company' => $company,
      'resources' => $resources,
      'source' => $source,
      'company2' => $otherCompany,
    ];
    $msg = clienttranslate('${company_name} pays ${resources_desc} to ${company_name2} for ${source}');

    self::notifyAll('collectResources', $msg, $data);
  }

  public static function recoverResources($company, $resources, $tile)
  {
    self::notifyAll(
      'collectResources',
      clienttranslate('${company_name} recovers ${resources_desc} and  ${nb} technology tile from the wheel'),
      ['company' => $company, 'resources' => $resources, 'tile' => $tile, 'nb' => count($tile)]
    );
  }

  public static function moveTokens($tokens)
  {
    self::notifyAll('collectResources', '', ['resources' => $tokens->toArray()]);
  }

  public static function rotateWheel($company, $nb)
  {
    self::notifyAll('rotateWheel', clienttranslate('${company_name} rotates the wheel'), [
      'company' => $company,
      'nb' => $nb,
    ]);
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

  public static function addDroplets($company, $meeples, $spaceId)
  {
    self::notifyAll('gainResources', clienttranslate('${company_name} place droplet(s) in headstream'), [
      'company' => $company,
      'resources' => $meeples,
      'spaceId' => $spaceId,
    ]);
  }

  public static function moveDroplet($droplet, $originalState)
  {
    self::notifyAll('moveDroplet', '', [
      'droplet' => [$droplet],
      'original' => $originalState,
    ]);
  }

  public static function produce($company, $energy, $droplets)
  {
    self::notifyAll(
      'produce',
      clienttranslate('${company_name} produces ${energy} energies with ${droplets} droplet(s)'),
      [
        'company' => $company,
        'energy' => $energy,
        'droplets' => $droplets,
      ]
    );
  }

  public function score($company, $amount, $source = null, $silent = false)
  {
    if ($source != null) {
      $msg = clienttranslate('${company_name} scores ${amount} VP(s) ${source}');
    } elseif ($silent) {
      $msg = '';
    } else {
      $msg = clienttranslate('${company_name} scores ${amount} VP(s)');
    }
    self::notifyAll('score', $msg, [
      'i18n' => ['source'],
      'company' => $company,
      'amount' => $amount,
      'source' => $source,
    ]);
  }

  public static function construct($company, $type, $target, $meeples, $technologyTlle)
  {
    self::notifyAll(
      'construct',
      clienttranslate('${company_name} constructs a ${type} in ${target} for ${resources_desc}'),
      [
        'company' => $company,
        'i18n' => ['type'],
        'type' => $type,
        'target' => $target,
        'resources' => $meeples,
        'techTile' => $technologyTlle,
      ]
    );
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

    if (isset($data['company2'])) {
      $data['company_name2'] = $data['company2']->getName();
      $data['company_id2'] = $data['company2']->getId();
      unset($data['company2']);
    }

    if (isset($data['resources'])) {
      // Get an associative array $resource => $amount
      $resources = Utils::reduceResources($data['resources']);
      $data['resources_desc'] = Utils::resourcesToStr($resources);
    }
  }
}

?>
