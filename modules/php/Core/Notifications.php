<?php

namespace BRG\Core;

use BRG\Managers\Players;
use BRG\Managers\TechnologyTiles;
use BRG\Managers\Buildings;
use BRG\Helpers\Utils;
use BRG\Core\Globals;
use BRG\ActionBoards\CompanyActionBoard;
use BRG\ActionBoards\OfficerActionBoard;

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

  public static function setAsideAdvancedTechTiles($ignored)
  {
    self::message(
      clienttranslate(
        'This game will be played without the following advanced technology tiles: ${tile1}, ${tile2} and ${tile3}'
      ),
      [
        'tile1' => $ignored[0],
        'tile2' => $ignored[1],
        'tile3' => $ignored[2],
      ]
    );
  }

  public static function changePhase($phase)
  {
    self::notifyAll('changePhase', '', [
      'phase' => $phase,
    ]);
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
      'bases' => $datas['bases'],
      'players' => $datas['players'],
      'companies' => $datas['companies'],
      'techTiles' => $datas['techTiles'],
      'contracts' => $datas['contracts'],
      'bonuses' => $datas['bonuses'],
      'works' => $datas['works'],
      //      'scores' => $datas['scores'],
    ];

    self::notifyAll('refreshUI', '', [
      'datas' => $fDatas,
    ]);
  }

  public static function placeBet($player, $pos, $vp)
  {
    $positionNames = [
      clienttranslate('first position'),
      clienttranslate('second position'),
      clienttranslate('third position'),
      clienttranslate('fourth position'),
    ];

    self::notifyAll('placeBet', clienttranslate('${player_name} is ready to pay ${vp}VP(s) for playing in ${pos}'), [
      'i18n' => ['pos'],
      'player' => $player,
      'vp' => $vp,
      'pos' => $positionNames[$pos],
    ]);
  }

  public static function auctionDone($order)
  {
    $orderDesc = ['log' => [], 'args' => []];
    foreach ($order as $i => $pId) {
      $orderDesc['log'][] = '${player_name' . $i . '}';
      $player = Players::get($pId);
      $orderDesc['args']['player_name' . $i] = $player->getName();
    }
    $orderDesc['log'] = join(', ', $orderDesc['log']);

    self::notifyAll(
      'auctionDone',
      clienttranslate('Auction is over, the first round will be played in the following order: ${order}'),
      [
        'order' => $orderDesc,
      ]
    );
  }

  public static function assignCompany($player, $company, $meeples, $tiles)
  {
    self::notifyAll('assignCompany', clienttranslate('${player_name} picks ${company_name}'), [
      'player' => $player,
      'company_name' => $company->getCname(),
      'company_id' => $company->getId(),
      'datas' => $company,
      'actionSpaces' => CompanyActionBoard::getUiData($company->getId()),
      'actionSpacesXO' => OfficerActionBoard::getUiData($company->getId()),
      'meeples' => $meeples->toArray(),
      'tiles' => $tiles->toArray(),
    ]);
  }

  public static function assignCompanyAutoma($company, $meeples, $tiles)
  {
    self::notifyAll('assignCompany', clienttranslate('Automa is playing ${company_name}'), [
      'company_name' => $company->getCname(),
      'company_id' => $company->getId(),
      'datas' => $company,
      'actionSpaces' => CompanyActionBoard::getUiData($company->getId()),
      'actionSpacesXO' => OfficerActionBoard::getUiData($company->getId()),
      'meeples' => $meeples->toArray(),
      'tiles' => $tiles->toArray(),
    ]);
  }

  public static function setupCompanies($meeples, $tiles)
  {
    self::notifyAll('setupCompanies', '', [
      'meeples' => $meeples->toArray(),
      'tiles' => $tiles->toArray(),
    ]);
  }

  public static function clearMatchups($contractIds)
  {
    self::notifyAll('clearMatchups', '', [
      'contractIds' => $contractIds,
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

  public static function discardContracts($company, $contracts)
  {
    self::notifyAll('silentDestroy', clienttranslate('${company_name} discards ${nb} contract(s)'), [
      'contracts' => $contracts,
      'nb' => count($contracts),
      'company' => $company,
    ]);
  }

  public static function refillContractStack($stack, $nContract)
  {
    self::notifyAll('refillContractStack', '', [
      'stack' => $stack,
      'nb' => $nContract,
    ]);
  }

  public static function discardTiles($tiles)
  {
    self::notifyAll('silentDestroy', clienttranslate('All advanced tiles are discarded'), [
      'tiles' => $tiles,
    ]);
  }

  public static function fulfillContract($company, $contract)
  {
    self::notifyAll('fulfillContract', clienttranslate('${company_name} fulfills one contract'), [
      'company' => $company,
      'contract' => $contract,
      'bonuses' => Game::get()->computeBonuses(),
    ]);
  }

  public static function fulfillExtWork($company, $work)
  {
    self::notifyAll('fulfillExtWork', clienttranslate('${company_name} fulfills one external work'), [
      'company' => $company,
      'work' => $work,
      'bonuses' => Game::get()->computeBonuses(),
    ]);
  }

  public static function refillStacks($contracts, $silent = false)
  {
    self::notifyAll(
      'refillStacks',
      $silent ? '' : clienttranslate('${n} new private contracts are drawn for refilling contracts stacks'),
      [
        'contracts' => $contracts->toArray(),
        'n' => $contracts->count(),
      ]
    );
  }

  public static function refillTechTiles($tiles)
  {
    self::notifyAll('refillTechTiles', clienttranslate('${n} new advanced tiles are drawn'), [
      'tiles' => $tiles->toArray(),
      'n' => $tiles->count(),
    ]);
  }

  public static function discardWorks($works)
  {
    self::notifyAll('silentDestroy', clienttranslate('All external works are discarded'), [
      'works' => $works,
    ]);
  }

  public static function refillExternalWorks($works)
  {
    self::notifyAll('refillExternalWorks', clienttranslate('${n} new external works are drawn'), [
      'works' => $works->toArray(),
      'n' => $works->count(),
    ]);
  }

  public static function startNewRound($round)
  {
    self::notifyAll('startNewRound', clienttranslate('Starting round n°${round}'), [
      'round' => $round,
      'bonuses' => Game::get()->computeBonuses(),
    ]);
  }

  public static function fillHeadstreams($meeples)
  {
    self::notifyAll('gainResources', clienttranslate('Filling headstreams'), [
      'resources' => $meeples->toArray(),
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

  public static function removeBonusTile($bonus)
  {
    self::notifyAll('silentDestroy', '', [
      'bonusTile' => $bonus,
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

  public static function payResourcesToWheel(
    $company,
    $tile,
    $deleted,
    $moved,
    $source,
    $cardSources = [],
    $cardNames = []
  ) {
    $data = [
      'company' => $company,
      'resources2' => $moved,
      'resources' => $deleted,
      'tile' => $tile,
    ];
    $msg = clienttranslate('${company_name} place ${resources_desc2} ${resources_desc} on the wheel');

    self::notifyAll('payResourcesToWheel', $msg, $data);
  }

  public static function recoverResources($company, $resources, $tile)
  {
    if (empty($resources)) {
      self::notifyAll(
        'recoverResources',
        clienttranslate('${company_name} recovers a technology tile from the wheel'),
        [
          'company' => $company,
          'resources' => [],
          'tile' => $tile,
        ]
      );
    } else {
      self::notifyAll(
        'recoverResources',
        clienttranslate('${company_name} recovers ${resources_desc} and a technology tile from the wheel'),
        [
          'company' => $company,
          'resources' => $resources->toArray(),
          'tile' => $tile,
        ]
      );
    }
  }

  public static function acquirePatent($company, $tile)
  {
    self::notifyAll(
      'collectResources',
      clienttranslate('${company_name} acquires a new technology tile (Patent office)'),
      [
        'company' => $company,
        'resources' => [],
        'tile' => $tile,
      ]
    );
  }

  public static function returnHomeEngineers($engineers)
  {
    self::notifyAll('collectResources', '', ['resources' => $engineers]);
  }

  public static function incEnergy($company, $token, $n, $energy)
  {
    self::notifyAll('incEnergy', '', [
      'company' => $company,
      'token' => $token,
      'n' => $n,
      'bonuses' => Game::get()->computeBonuses(),
      'trackReward' => $company->getEnergyTrackReward(),
    ]);
  }

  public static function resetEnergies($tokens)
  {
    self::notifyAll('resetEnergies', '', ['tokens' => $tokens->toArray()]);
  }

  public static function flipToken($tokenId)
  {
    self::notifyAll('flipToken', '', ['token' => $tokenId]);
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
      'bonuses' => Game::get()->computeBonuses(),
    ]);
  }

  public static function addDroplets($company, $meeples, $spaceId, $flowing, $headstreams)
  {
    $msg = $flowing
      ? clienttranslate('${company_name} places ${n} flowing droplet(s) in headstream(s) ${headstreams}')
      : clienttranslate('${company_name} places ${n} droplet(s) in headstream(s) ${headstreams}');
    self::notifyAll('gainResources', $msg, [
      'company' => $company,
      'resources' => $meeples,
      'spaceId' => $spaceId,
      'headstreams' => join(', ', $headstreams),
      'n' => count($meeples),
    ]);
  }

  public static function addAutoDroplets($company, $meeples)
  {
    self::notifyAll(
      'gainResources',
      clienttranslate('${company_name} places ${n} droplet(s) in dams (technology tile effect)'),
      [
        'company' => $company,
        'resources' => $meeples,
        'n' => count($meeples),
      ]
    );
  }

  public static function moveDroplets($droplets)
  {
    self::notifyAll('moveDroplets', '', ['droplets' => $droplets->toArray()]);
  }

  public static function updateTurnOrder($order)
  {
    self::notifyAll('updateTurnOrder', '', ['order' => $order]);
  }

  public static function produce($company, $basinSpaceId, $powerhouseSpaceId, $energy, $droplets, $germanPower)
  {
    $msg = $germanPower
      ? clienttranslate(
        '${company_name} produces ${energy} energy units at plant ${powerhouse} with ${droplets} droplet(s) from basin ${basin} (nation\'s power)'
      )
      : clienttranslate(
        '${company_name} produces ${energy} energy units at plant ${powerhouse} with ${droplets} droplet(s) from basin ${basin}'
      );

    self::notifyAll('produce', $msg, [
      'company' => $company,
      'powerhouse' => $powerhouseSpaceId,
      'basin' => $basinSpaceId,
      'energy' => $energy,
      'droplets' => $droplets,
      'bonuses' => Game::get()->computeBonuses(),
      'trackReward' => $company->getEnergyTrackReward(),
    ]);
  }

  public static function score($company, $amount, $total, $source = null, $silent = false)
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
      'total' => $total,
    ]);
  }

  public static function placeStructure($company, $type, $target, $meeple)
  {
    $typeDescs = [
      BASE => clienttranslate('a Base'),
      ELEVATION => clienttranslate('an Elevation'),
      CONDUIT => clienttranslate('a Conduit'),
      POWERHOUSE => clienttranslate('a Powerhouse'),
    ];

    $targetName = $target;
    if ($type == CONDUIT) {
      $targetName = str_replace('L', 'A', $targetName);
      $targetName = str_replace('R', 'B', $targetName);
    }

    $msg = clienttranslate('${company_name} constructs ${type_desc} in ${target_name}');
    if ($type == BUILDING) {
      $msg = clienttranslate('${company_name} constructs ${type_desc}');
      $bId = explode('-', $target)[1];
      $typeDesc = Buildings::get($bId)->getName();
    } else {
      $typeDesc = $typeDescs[$type];
    }

    self::notifyAll('construct', $msg, [
      'company' => $company,
      'i18n' => ['type_desc'],
      'type_desc' => $typeDesc,
      'target' => $target,
      'target_name' => $targetName,
      'meeple' => $meeple,
      'bonuses' => Game::get()->computeBonuses(),
    ]);
  }

  public static function newIncomeRevealed($company)
  {
    self::notifyAll('updateIncome', clienttranslate('${company_name} reveals a new income. A bonus will be earned.'), [
      'company' => $company,
      'incomes' => $company->getIncomes(),
    ]);
  }

  public static function mahiriCopy($company, $officer)
  {
    self::notifyAll(
      'mahiriCopy',
      clienttranslate('${company_name} copys power of ${officer_name} with Mahiri\'s power'),
      [
        'i18n' => ['officer_name'],
        'company' => $company,
        'officer_name' => $officer->getName(),
        'officer_id' => $officer->getId(),
      ]
    );
  }

  public static function clearMahiri()
  {
    self::notifyAll('clearMahiri', '', []);
  }

  public static function flipAutomaCard($cardBack, $cardFront)
  {
    self::notifyAll('flipAutomaCard', '', [
      'cardBack' => $cardBack,
      'cardFront' => $cardFront,
    ]);
  }

  public static function emptyContractStack($company, $stack, $ids)
  {
    $stacks = [
      2 => clienttranslate('green contracts'),
      3 => clienttranslate('yellow contracts'),
      4 => clienttranslate('red contracts'),
    ];

    self::notifyAll('emptyContractStack', clienttranslate('${company_name} removes ${contracts}'), [
      'i18n' => ['contracts'],
      'company' => $company,
      'contracts' => $stacks[$stack],
      'contractIds' => $ids,
      'stack' => $stack,
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

    if (isset($data['resources2'])) {
      // Get an associative array $resource => $amount
      $resources = Utils::reduceResources($data['resources2']);
      $data['resources_desc2'] = Utils::resourcesToStr($resources);
    }
  }
}
