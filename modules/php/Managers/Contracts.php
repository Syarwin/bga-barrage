<?php
namespace BRG\Managers;
use \BRG\Helpers\Utils;

/* Class to manage all the contracts for Barrage */

class Contracts extends \BRG\Helpers\Pieces
{
  protected static $table = 'contracts';
  protected static $prefix = 'contract_';
  protected static $customFields = ['type'];

  protected static function cast($meeple)
  {
    return [
      'id' => (int) $meeple['id'],
      'location' => $meeple['location'],
      'state' => $meeple['state'],
      'type' => $meeple['type'],
    ];
  }

  public static function getUiData()
  {
    return self::getSelectQuery()
      ->get()
      ->toArray();
  }

  /* Creation of various meeples */
  public static function setupNewGame()
  {
    $contracts = [];

    // Create National contracts

    // Create Private contracts

    // Create starting contracts

    self::create($contracts);
  }

  public function randomStartingPick($nPlayers)
  {
    $contractIds = Utils::rand(STARTING_CONTRACTS, $nPlayers);
    self::move($contractIds, 'pickStart');
  }
}
