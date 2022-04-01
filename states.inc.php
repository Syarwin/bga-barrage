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
    'transitions' => ['' => ST_FOO],
  ],

  ST_FOO => [
    'name' => 'playerTurn',
    'description' => clienttranslate('${actplayer} must play a card or pass'),
    'descriptionmyturn' => clienttranslate('${you} must play a card or pass'),
    'type' => 'activeplayer',
    'possibleactions' => ['actPlayCard', 'actPpass'],
    'transitions' => ['done' => ST_FOO],
  ],

  ST_BEFORE_START_OF_TURN => [
    'name' => 'beforeStartOfTurn',
    'description' => '',
    'type' => 'game',
    'action' => 'stBeforeStartOfTurn',
    'updateGameProgression' => true,
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

  ST_PREPARATION => [
    'name' => 'preparation',
    'description' => '',
    'type' => 'game',
    'action' => 'stPreparation',
    'updateGameProgression' => true,
  ],

  ST_PLACE_ENGINEER => [
    'name' => 'placeFarmer',
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
    'description' => clienttranslate('${actplayer} must build'),
    'descriptionmyturn' => clienttranslate('${you} must build'),
    'descriptionskippable' => clienttranslate('${actplayer} may build'),
    'descriptionmyturnskippable' => clienttranslate('${you} may build'),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actConstruct', 'actPassOptionalAction', 'actRestart'],
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
    'possibleactions' => ['actContract', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_PLACE_DROPLET => [
    'name' => 'placeDroplet',
    'description' => clienttranslate('${actplayer} must choose where to place the droplet(s)'),
    'descriptionmyturn' => clienttranslate('${you} must choose where to place the droplet(s)'),
    'descriptionskippable' => clienttranslate('${actplayer} may place droplet(s)'),
    'descriptionmyturnskippable' => clienttranslate('${you} may place droplet(s)'),
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
    'description' => clienttranslate('${actplayer} must take ${nb} available contract(s)'),
    'descriptionmyturn' => clienttranslate('${you} must take ${nb} available contract(s)'),
    'descriptionskippable' => clienttranslate('${actplayer} may take ${nb} available contract(s)'),
    'descriptionmyturnskippable' => clienttranslate('${you} may take ${nb} available contract(s)'),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actTakeContract', 'actPassOptionalAction', 'actRestart'],
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
