<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Barrage implementation :© Timothe Pecatte <tim.pecatte@gmail.com>, Vincent Toper <vincent.toper@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * states.inc.php
 *
 * Barrage game states description
 *
 */

$machinestates = [
  // The initial state. Please do not modify.
  ST_GAME_SETUP => [
    'name' => 'gameSetup',
    'description' => '',
    'type' => 'manager',
    'action' => 'stGameSetup',
    'transitions' => ['' => ST_SETUP_BRANCH],
  ],

  ST_GENERIC_NEXT_PLAYER => [
    'name' => 'genericNextPlayer',
    'type' => 'game',
  ],

  ST_SETUP_BRANCH => [
    'name' => 'setupBranch',
    'description' => '',
    'type' => 'game',
    'action' => 'stSetupBranch',
    'transitions' => ['pick' => ST_PICK_START, 'start' => ST_BEFORE_START_OF_ROUND],
  ],

  ST_PICK_START_NEXT => [
    'name' => 'pickStartNext',
    'description' => '',
    'type' => 'game',
    'action' => 'stPickStartNext',
    'transitions' => ['pick' => ST_PICK_START, 'done' => ST_BEFORE_START_OF_ROUND],
  ],

  ST_PICK_START => [
    'name' => 'pickStart',
    'description' => clienttranslate('${actplayer} must choose a company/officer pair and a starting contract'),
    'descriptionmyturn' => clienttranslate('${you} must choose a company/officer pair and a starting contract'),
    'type' => 'activeplayer',
    'args' => 'argsPickStart',
    'possibleactions' => ['actPickStart'],
    'transitions' => ['nextPick' => ST_PICK_START_NEXT],
  ],

  ST_FREE_PICK_START => [
    'name' => 'freePickStart',
    'description' => clienttranslate('${actplayer} must choose a company, an officer and a starting contract'),
    'descriptionmyturn' => clienttranslate('${you} must choose a company, an officer and a starting contract'),
    'type' => 'activeplayer',
    'args' => 'argsFreePickStart',
    'action' => 'stFreePickStart',
    'possibleactions' => ['actFreePickStart'],
    'transitions' => ['done' => ST_BEFORE_START_OF_ROUND],
  ],

  ST_BEFORE_START_OF_ROUND => [
    'name' => 'beforeStartOfRound',
    'description' => '',
    'type' => 'game',
    'action' => 'stBeforeStartOfRound',
    'updateGameProgression' => true,
    'transitions' => ['' => ST_START_OF_ROUND],
  ],

  ST_START_OF_ROUND => [
    'name' => 'startOfRound',
    'description' => '',
    'type' => 'game',
    'action' => 'stStartOfRound',
    'updateGameProgression' => true,
  ],

  ST_ACTION_PHASE => [
    'name' => 'actionPhase',
    'description' => '',
    'type' => 'game',
    'action' => 'stActionPhase',
    'transitions' => [],
  ],

  ST_RESOLVE_STACK => [
    'name' => 'resolveStack',
    'type' => 'game',
    'action' => 'stResolveStack',
    'transitions' => [],
  ],

  ST_CONFIRM_TURN => [
    'name' => 'confirmTurn',
    'description' => clienttranslate('${actplayer} must confirm or restart their turn'),
    'descriptionmyturn' => clienttranslate('${you} must confirm or restart your turn'),
    'type' => 'activeplayer',
    'args' => 'argsConfirmTurn',
    'action' => 'stConfirmTurn',
    'possibleactions' => ['actConfirmTurn', 'actRestart'],
  ],

  ST_CONFIRM_PARTIAL_TURN => [
    'name' => 'confirmPartialTurn',
    'description' => clienttranslate('${actplayer} must confirm the switch of player'),
    'descriptionmyturn' => clienttranslate(
      '${you} must confirm the switch of player. You will not be able to restart turn'
    ),
    'type' => 'activeplayer',
    'args' => 'argsConfirmTurn',
    // 'action' => 'stConfirmPartialTurn',
    'possibleactions' => ['actConfirmPartialTurn', 'actRestart'],
  ],

  ST_RESOLVE_CHOICE => [
    'name' => 'resolveChoice',
    'description' => clienttranslate('${actplayer} must choose an action'),
    'descriptionmyturn' => clienttranslate('${you} must choose an action'),
    'type' => 'activeplayer',
    'args' => 'argsResolveChoice',
    'possibleactions' => ['actChooseAction', 'actRestart'],
    'transitions' => [],
  ],

  ST_PLACE_ENGINEER => [
    'name' => 'placeEngineer',
    'description' => clienttranslate('${actplayer} must place an engineer'),
    'descriptionmyturn' => clienttranslate('${you} must place an engineer'),
    'descriptionskippable' => clienttranslate('${actplayer} may place an engineer'),
    'descriptionmyturnskippable' => clienttranslate('${you} may place a engineer'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actPlaceEngineer', 'actPassOptionalAction', 'actRestart'],
    'transitions' => [],
  ],

  ST_GAIN => [
    'name' => 'gainResources',
    'type' => 'game',
    'action' => 'stAtomicAction',
  ],

  ST_CONSTRUCT => [
    'name' => 'construct',
    'description' => clienttranslate('${actplayer} must build a structure'),
    'descriptionmyturn' => clienttranslate('${you} must select a technology tile and a space to build a structure'),
    'descriptionskippable' => clienttranslate('${actplayer} may build a structure'),
    'descriptionmyturnskippable' => clienttranslate(
      '${you} may select a technology tile and a space to build a structure'
    ),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actConstruct', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_PLACE_STRUCTURE => [
    'name' => 'placeStructure',
    'description' => clienttranslate('${actplayer} must place a structure (${structure}'),
    'descriptionmyturn' => clienttranslate('${you} must place a structure (${structure})'),
    'descriptionskippable' => clienttranslate('${actplayer} may place a structure (${structure})'),
    'descriptionmyturnskippable' => clienttranslate('${you} may place a structure (${structure})'),
    'descriptionauto' => clienttranslate('${actplayer} place a structure (${structure})'),
    'descriptionmyturnauto' => clienttranslate('${you} place a structure (${structure})'),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actPlaceStructure', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_PAY => [
    'name' => 'payResources',
    'description' => clienttranslate('${actplayer} must choose how to pay for ${source}'),
    'descriptionmyturn' => clienttranslate('${you} must choose how to pay for ${source}'),
    'descriptionauto' => clienttranslate('${actplayer} pays for ${source}'),
    'descriptionmyturnauto' => clienttranslate('${you} pay for ${source}'),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actPay', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_PRODUCT => [
    'name' => 'product',
    'description' => clienttranslate('${actplayer} must product energy ${modifier}'),
    'descriptionmyturn' => clienttranslate('${you} must choose where to product energy ${modifier}'),
    'descriptionskippable' => clienttranslate('${actplayer} may product energy ${modifier})'),
    'descriptionmyturnskippable' => clienttranslate('${you} may product energy ${modifier}'),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actProduct', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_FULFILL_CONTRACT => [
    'name' => 'fulfillContract',
    'description' => clienttranslate('${actplayer} may choose a contract to fulfill'),
    'descriptionmyturn' => clienttranslate('${you} may choose a contract to fulfill'),
    'descriptionskippable' => clienttranslate('${actplayer} may choose a contract to fulfill'),
    'descriptionmyturnskippable' => clienttranslate('${you} may choose a contract to fulfill'),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actFulfillContract', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_PLACE_DROPLET => [
    'name' => 'placeDroplet',
    'description' => clienttranslate('${actplayer} must choose where to place the ${n} droplet(s)'),
    'descriptionmyturn' => clienttranslate('${you} must choose where to place the ${n} droplet(s)'),
    'descriptionskippable' => clienttranslate('${actplayer} may place ${n} droplet(s)'),
    'descriptionmyturnskippable' => clienttranslate('${you} may place ${n} droplet(s)'),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actPlaceDroplet', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_ROTATE_WHEEL => [
    'name' => 'rotateWheel',
    'type' => 'game',
    'action' => 'stAtomicAction',
  ],

  ST_TAKE_CONTRACT => [
    'name' => 'takeContract',
    'description' => clienttranslate('${actplayer} must take ${n} available contract(s)'),
    'descriptionmyturn' => clienttranslate('${you} must take ${n} available contract(s)'),
    'descriptionskippable' => clienttranslate('${actplayer} may take ${n} available contract(s)'),
    'descriptionmyturnskippable' => clienttranslate('${you} may take ${n} available contract(s)'),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actTakeContract', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_DISCARD_CONTRACT => [
    'name' => 'discardContract',
    'description' => clienttranslate('${actplayer} must discard ${nb} contract(s)'),
    'descriptionmyturn' => clienttranslate('${you} must discard ${nb} contract(s)'),
    'descriptionskippable' => clienttranslate('${actplayer} may discard ${nb} contract(s)'),
    'descriptionmyturnskippable' => clienttranslate('${you} may discard ${nb} contract(s)'),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actDiscardContract', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_SPECIAL_EFFECT => [
    'name' => 'specialEffect',
    'description' => '',
    'descriptionmyturn' => '',
    'action' => 'stAtomicAction',
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actPassOptionalAction', 'actRestart'],
  ],

  ST_RETURNING_HOME => [
    'name' => 'endOfTurn',
    'description' => '',
    'type' => 'game',
    'action' => 'stReturnHome',
  ],

  // Final state.
  // Please do not modify (and do not overload action/args methods).
  ST_END_GAME => [
    'name' => 'gameEnd',
    'description' => clienttranslate('End of game'),
    'type' => 'manager',
    'action' => 'stGameEnd',
    'args' => 'argGameEnd',
  ],
];
