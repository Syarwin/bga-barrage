<?php

namespace BRG\Core;

use BRG\Core\Game;
/*
 * Globals
 */

class Globals extends \BRG\Helpers\DB_Manager
{
  protected static $initialized = false;
  protected static $variables = [
    'engine' => 'obj', // DO NOT MODIFY, USED IN ENGINE MODULE
    'engineChoices' => 'int', // DO NOT MODIFY, USED IN ENGINE MODULE
    'callbackEngineResolved' => 'obj', // DO NOT MODIFY, USED IN ENGINE MODULE

    'customTurnOrders' => 'obj', // DO NOT MODIFY, USED FOR CUSTOM TURN ORDER FEATURE
    'turnOrder' => 'obj', // store the current turn order
    'activeCompany' => 'int', // store the id of active company

    // Game options
    'setup' => 'int',
    'lWP' => 'bool',
    'newXO' => 'bool',
    'map' => 'int',
    'countCompanies' => 'int', // Useful when companies DB is not filled up yet

    // Storage
    'headstreams' => 'obj',
    'bonusTiles' => 'obj',
    'objectiveTile' => 'int',
    'startingMatchups' => 'obj', // Used for the setup "draft" phase
    'round' => 'int',
    'phase' => 'str',

    'skippedCompanies' => 'obj',

    'antonPower' => 'str',
    'mahiriPower' => 'int',
    'mahiriAddXO' => 'obj',
    'aI' => 'bool',

    'auction' => 'obj',
    'auctionOrder' => 'obj',
  ];

  protected static $table = 'global_variables';
  protected static $primary = 'name';
  protected static function cast($row)
  {
    $val = json_decode(\stripslashes($row['value']), true);
    return (self::$variables[$row['name']] ?? null) == 'int' ? ((int) $val) : $val;
  }

  /*
   * Fetch all existings variables from DB
   */
  protected static $data = [];
  public static function fetch()
  {
    // Turn of LOG to avoid infinite loop (Globals::isLogging() calling itself for fetching)
    $tmp = self::$log;
    self::$log = false;

    foreach (self::DB()
        ->select(['value', 'name'])
        ->get()
      as $name => $variable) {
      if (\array_key_exists($name, self::$variables)) {
        self::$data[$name] = $variable;
      }
    }
    self::$initialized = true;
    self::$log = $tmp;
  }

  /*
   * Create and store a global variable declared in this file but not present in DB yet
   *  (only happens when adding globals while a game is running)
   */
  public static function create($name)
  {
    if (!\array_key_exists($name, self::$variables)) {
      return;
    }

    $default = [
      'int' => 0,
      'obj' => [],
      'bool' => false,
      'str' => '',
    ];
    $val = $default[self::$variables[$name]];
    self::DB()->insert(
      [
        'name' => $name,
        'value' => \json_encode($val),
      ],
      true
    );
    self::$data[$name] = $val;
  }

  /*
   * Magic method that intercept not defined static method and do the appropriate stuff
   */
  public static function __callStatic($method, $args)
  {
    if (!self::$initialized) {
      self::fetch();
    }

    if (preg_match('/^([gs]et|inc|is)([A-Z])(.*)$/', $method, $match)) {
      // Sanity check : does the name correspond to a declared variable ?
      $name = mb_strtolower($match[2]) . $match[3];
      if (!\array_key_exists($name, self::$variables)) {
        throw new \InvalidArgumentException("Property {$name} doesn't exist");
      }

      // Create in DB if don't exist yet
      if (!\array_key_exists($name, self::$data)) {
        self::create($name);
      }

      if ($match[1] == 'get') {
        // Basic getters
        return self::$data[$name];
      } elseif ($match[1] == 'is') {
        // Boolean getter
        if (self::$variables[$name] != 'bool') {
          throw new \InvalidArgumentException("Property {$name} is not of type bool");
        }
        return (bool) self::$data[$name];
      } elseif ($match[1] == 'set') {
        // Setters in DB and update cache
        if (!isset($args[0])) {
          throw new \InvalidArgumentException("Setting {$name} require a value");
        }
        $value = $args[0];
        if (self::$variables[$name] == 'int') {
          $value = (int) $value;
        }
        if (self::$variables[$name] == 'bool') {
          $value = (bool) $value;
        }

        self::$data[$name] = $value;
        self::DB()->update(['value' => \addslashes(\json_encode($value))], $name);
        return $value;
      } elseif ($match[1] == 'inc') {
        if (self::$variables[$name] != 'int') {
          throw new \InvalidArgumentException("Trying to increase {$name} which is not an int");
        }

        $getter = 'get' . $match[2] . $match[3];
        $setter = 'set' . $match[2] . $match[3];
        return self::$setter(self::$getter() + (empty($args) ? 1 : $args[0]));
      }
    }
    return null;
  }

  /*
   * Setup new game
   */
  public static function setupNewGame($players, &$options)
  {
    self::setSetup($options[\BRG\OPTION_SETUP]);
    self::setLWP(($options[\BRG\OPTION_EXPANSION_LWP] ?? null) == \BRG\OPTION_EXPANSION_LWP_ON);
    self::setNewXO(($options[\BRG\OPTION_NEW_XO] ?? null) == \BRG\OPTION_NEW_XO_ON);
    self::setCountCompanies(count($players) + $options[\BRG\OPTION_AUTOMA]);
    self::setMahiriAddXO([]);
    self::setRound(0);
    self::setAI($options[\BRG\OPTION_AUTOMA] > 0);

    $map = ($options[\BRG\OPTION_MAP] ?? 0) == 0 ? MAP_BASE : \MAP_5P;
    self::setMap($map);
  }

  public static function isBeginner()
  {
    return self::getSetup() == \BRG\OPTION_SETUP_BEGINNER;
  }
}
