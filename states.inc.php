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
    'transitions' => ['nextPick' => ST_PICK_START_NEXT, 'pick' => ST_PICK_START, 'done' => ST_BEFORE_START_OF_ROUND],
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

  ST_PRE_AUTOMA_TURN => [
    'name' => 'preAutomaTurn',
    'description' => '',
    'action' => 'stPreAutomaTurn',
    'type' => 'game',
    'transitions' => ['' => ST_AUTOMA_TURN],
  ],

  ST_AUTOMA_TURN => [
    'name' => 'automaTurn',
    'description' => 'AUTOMA TURN',
    'type' => 'active', // TODO
    'possibleactions' => ['actRunAutoma'],
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
    'action' => 'stAtomicAction',
    'possibleactions' => ['actPlaceEngineer', 'actPassOptionalAction', 'actRestart', 'actSkip'],
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
    'descriptionmyturngeneric' => clienttranslate(
      '${you} must select a technology tile and a space to build a structure'
    ),
    'descriptionskippable' => clienttranslate('${actplayer} may build a structure'),
    'descriptionmyturnskippable' => clienttranslate(
      '${you} may select a technology tile and a space to build a structure'
    ),
    'descriptionmyturnselectTile' => clienttranslate('${you} must select a technology tile'),
    'descriptionmyturnselectSpace' => clienttranslate('${you} must select a space on the map to build a structure'),
    'descriptionmyturnconfirm' => clienttranslate('Please confirm your choice'),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actConstruct', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_PLACE_STRUCTURE => [
    'name' => 'placeStructure',
    'description' => clienttranslate('${actplayer} must place a structure (${structure})'),
    'descriptionmyturn' => clienttranslate('${you} must place a structure (${structure})'),
    'descriptionskippable' => clienttranslate('${actplayer} may place a structure (${structure})'),
    'descriptionmyturnskippable' => clienttranslate('${you} may place a structure (${structure})'),
    'descriptionauto' => clienttranslate('${actplayer} place a structure (${structure})'),
    'descriptionmyturnauto' => clienttranslate('${you} place a structure (${structure})'),
    'descriptionconstraints' => clienttranslate('${you} place a structure (${structure}) in ${location}'),
    'descriptionmyturnconstraints' => clienttranslate('${you} place a structure (${structure}) in ${location}'),
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

  ST_PRODUCE => [
    'name' => 'produce',
    'description' => clienttranslate('${actplayer} must produce energy ${modifier}'),
    'descriptionmyturn' => clienttranslate('${you} must choose where to produce energy ${modifier}'),
    'descriptionskippable' => clienttranslate('${actplayer} may produce energy ${modifier}'),
    'descriptionmyturnskippable' => clienttranslate('${you} may produce energy ${modifier}'),
    'descriptiongermanyskippable' => clienttranslate(
      '(Germany) ${actplayer} may produce energy with a distinct powerhouse (no bonus/malus)'
    ),
    'descriptionmyturngermanyskippable' => clienttranslate(
      '(Germany) ${you} may produce energy with a distinct powerhouse (no bonus/malus)'
    ),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actProduce', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_FULFILL_CONTRACT => [
    'name' => 'fulfillContract',
    'description' => '',
    'descriptionmyturn' => '',
    'descriptionskippable' => clienttranslate(
      '${actplayer} may fulfill a contract with an energy requirement of at most ${n}'
    ),
    'descriptionmyturnskippable' => clienttranslate(
      '${you} may fulfill a contract with an energy requirement of at most ${n}'
    ),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actFulfillContract', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_PLACE_DROPLET => [
    'name' => 'placeDroplet',
    'description' => clienttranslate('${actplayer} must choose where to place the ${n} droplet(s) (${speed})'),
    'descriptionmyturn' => clienttranslate('${you} must choose where to place the ${n} droplet(s) (${speed})'),
    'descriptionskippable' => clienttranslate('${actplayer} may place ${n} droplet(s) (${speed})'),
    'descriptionmyturnskippable' => clienttranslate('${you} may place ${n} droplet(s) (${speed})'),
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

  ST_PATENT => [
    'name' => 'patent',
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
    'description' => clienttranslate('${actplayer} must discard ${n} contract(s)'),
    'descriptionmyturn' => clienttranslate('${you} must discard ${n} contract(s)'),
    'descriptionskippable' => clienttranslate('${actplayer} may discard ${n} contract(s)'),
    'descriptionmyturnskippable' => clienttranslate('${you} may discard ${n} contract(s)'),
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
    'possibleactions' => ['actPassOptionalAction', 'actRestart', 'actCopyPower'],
  ],

  ST_TILE_EFFECT => [
    'name' => 'tileEffect',
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
    'transitions' => ['end' => ST_PRE_END_OF_GAME, 'next' => ST_BEFORE_START_OF_ROUND],
    'action' => 'stReturnHome',
  ],

  ST_PRE_END_OF_GAME => [
    'name' => 'endScoring',
    'description' => '',
    'type' => 'game',
    'transitions' => ['' => ST_END_GAME],
    'updateGameProgression' => true,
    'action' => 'stEndScoring',
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
