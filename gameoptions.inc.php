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
 * gameoptions.inc.php
 *
 * !! It is not a good idea to modify this file when a game is running !!
 *
 */

namespace BRG;

require_once('modules/php/gameoptions.inc.php');

$game_options = [
  OPTION_AUTOMA => [
    'name' => totranslate('Automas'),
    'values' => [
      0 => [
        'name' => clienttranslate('0'),
        'description' => clienttranslate('Only human players'),
      ],
      1 => [
        'name' => clienttranslate('1 automa'),
        'tmdisplay' => clienttranslate('[1 automa]'),
      ],
      2 => [
        'name' => clienttranslate('2 automas'),
        'tmdisplay' => clienttranslate('[2 automas]'),
      ],
      3 => [
        'name' => clienttranslate('3 automas'),
        'tmdisplay' => clienttranslate('[3 automas]'),
      ],
      4 => [
        'name' => clienttranslate('4 automas'),
        'tmdisplay' => clienttranslate('[4 automas]'),
      ],
    ],
    'startcondition' => [
      0 => [
        [
          'type' => 'minplayers',
          'value' => 2,
          'message' => clienttranslate('You can\'t play solo without an automa'),
        ],
      ],
      1 => [
        [
          'type' => 'maxplayers',
          'value' => 4,
          'message' => clienttranslate('Number of automas + players can\'t exceed 5'),
        ],
      ],
      2 => [
        [
          'type' => 'maxplayers',
          'value' => 3,
          'message' => clienttranslate('Number of automas + players can\'t exceed 5'),
        ],
      ],
      3 => [
        [
          'type' => 'maxplayers',
          'value' => 2,
          'message' => clienttranslate('Number of automas + players can\'t exceed 5'),
        ],
      ],
      1 => [
        [
          'type' => 'maxplayers',
          'value' => 4,
          'message' => clienttranslate('Number of automas + players can\'t exceed 5'),
        ],
      ],
    ],
  ],

  OPTION_COMPANY_1 => [
    'name' => totranslate('Company of the first player at the table'),
    'values' => [
      RANDOM => [
        'name' => clienttranslate('Random'),
      ],
      COMPANY_USA => [
        'name' => clienttranslate('USA'),
        'tmdisplay' => clienttranslate('[USA:1]'),
      ],
      COMPANY_GERMANY => [
        'name' => clienttranslate('Germany'),
        'tmdisplay' => clienttranslate('[De:1]'),
      ],
      COMPANY_ITALY => [
        'name' => clienttranslate('Italy'),
        'tmdisplay' => clienttranslate('[It:1]'),
      ],
      COMPANY_FRANCE => [
        'name' => clienttranslate('France'),
        'tmdisplay' => clienttranslate('[Fr:1]'),
      ],
      COMPANY_NETHERLANDS => [
        'name' => clienttranslate('Netherlands'),
        'tmdisplay' => clienttranslate('[NL:1]'),
      ],
    ],
  ],
  OPTION_COMPANY_2 => [
    'name' => totranslate('Company of the second player at the table'),
    'values' => [
      RANDOM => [
        'name' => clienttranslate('Random'),
      ],
      COMPANY_USA => [
        'name' => clienttranslate('USA'),
        'tmdisplay' => clienttranslate('[USA:2]'),
      ],
      COMPANY_GERMANY => [
        'name' => clienttranslate('Germany'),
        'tmdisplay' => clienttranslate('[De:2]'),
      ],
      COMPANY_ITALY => [
        'name' => clienttranslate('Italy'),
        'tmdisplay' => clienttranslate('[It:2]'),
      ],
      COMPANY_FRANCE => [
        'name' => clienttranslate('France'),
        'tmdisplay' => clienttranslate('[Fr:2]'),
      ],
      COMPANY_NETHERLANDS => [
        'name' => clienttranslate('Netherlands'),
        'tmdisplay' => clienttranslate('[NL:2]'),
      ],
    ],
    'displaycondition' => [
      [
        'type' => 'minplayers',
        'value' => [2, 3, 4, 5],
      ],
    ],
  ],
  OPTION_COMPANY_3 => [
    'name' => totranslate('Company of the third player at the table'),
    'values' => [
      RANDOM => [
        'name' => clienttranslate('Random'),
      ],
      COMPANY_USA => [
        'name' => clienttranslate('USA'),
        'tmdisplay' => clienttranslate('[USA:3]'),
      ],
      COMPANY_GERMANY => [
        'name' => clienttranslate('Germany'),
        'tmdisplay' => clienttranslate('[De:3]'),
      ],
      COMPANY_ITALY => [
        'name' => clienttranslate('Italy'),
        'tmdisplay' => clienttranslate('[It:3]'),
      ],
      COMPANY_FRANCE => [
        'name' => clienttranslate('France'),
        'tmdisplay' => clienttranslate('[Fr:3]'),
      ],
      COMPANY_NETHERLANDS => [
        'name' => clienttranslate('Netherlands'),
        'tmdisplay' => clienttranslate('[NL:3]'),
      ],
    ],
    'displaycondition' => [
      [
        'type' => 'minplayers',
        'value' => [3, 4, 5],
      ],
    ],
  ],
  OPTION_COMPANY_4 => [
    'name' => totranslate('Company of the fourth player at the table'),
    'values' => [
      RANDOM => [
        'name' => clienttranslate('Random'),
      ],
      COMPANY_USA => [
        'name' => clienttranslate('USA'),
        'tmdisplay' => clienttranslate('[USA:4]'),
      ],
      COMPANY_GERMANY => [
        'name' => clienttranslate('Germany'),
        'tmdisplay' => clienttranslate('[De:4]'),
      ],
      COMPANY_ITALY => [
        'name' => clienttranslate('Italy'),
        'tmdisplay' => clienttranslate('[It:4]'),
      ],
      COMPANY_FRANCE => [
        'name' => clienttranslate('France'),
        'tmdisplay' => clienttranslate('[Fr:4]'),
      ],
      COMPANY_NETHERLANDS => [
        'name' => clienttranslate('Netherlands'),
        'tmdisplay' => clienttranslate('[NL:4]'),
      ],
    ],
    'displaycondition' => [
      [
        'type' => 'minplayers',
        'value' => [4, 5],
      ],
    ],
  ],
  OPTION_COMPANY_5 => [
    'name' => totranslate('Company of first player at the table'),
    'values' => [
      RANDOM => [
        'name' => clienttranslate('Random'),
      ],
      COMPANY_USA => [
        'name' => clienttranslate('USA'),
        'tmdisplay' => clienttranslate('[USA:5]'),
      ],
      COMPANY_GERMANY => [
        'name' => clienttranslate('Germany'),
        'tmdisplay' => clienttranslate('[De:5]'),
      ],
      COMPANY_ITALY => [
        'name' => clienttranslate('Italy'),
        'tmdisplay' => clienttranslate('[It:5]'),
      ],
      COMPANY_FRANCE => [
        'name' => clienttranslate('France'),
        'tmdisplay' => clienttranslate('[Fr:5]'),
      ],
      COMPANY_NETHERLANDS => [
        'name' => clienttranslate('Netherlands'),
        'tmdisplay' => clienttranslate('[NL:5]'),
      ],
    ],
    'displaycondition' => [
      [
        'type' => 'minplayers',
        'value' => [5],
      ],
    ],
  ],

  OPTION_AUTOMA_1 => [
    'name' => totranslate('Company of the first automa'),
    'values' => [
      RANDOM => [
        'name' => clienttranslate('Random'),
      ],
      COMPANY_USA => [
        'name' => clienttranslate('USA'),
        'tmdisplay' => clienttranslate('[USA:AI 1]'),
      ],
      COMPANY_GERMANY => [
        'name' => clienttranslate('Germany'),
        'tmdisplay' => clienttranslate('[De:AI 1]'),
      ],
      COMPANY_ITALY => [
        'name' => clienttranslate('Italy'),
        'tmdisplay' => clienttranslate('[It:AI 1]'),
      ],
      COMPANY_FRANCE => [
        'name' => clienttranslate('France'),
        'tmdisplay' => clienttranslate('[Fr:AI 1]'),
      ],
      COMPANY_NETHERLANDS => [
        'name' => clienttranslate('Netherlands'),
        'tmdisplay' => clienttranslate('[NL:AI 1]'),
      ],
    ],
    'displaycondition' => [
      [
        'type' => 'otheroption',
        'id' => OPTION_AUTOMA,
        'value' => [1, 2, 3, 4],
      ],
    ],
  ],
  OPTION_AUTOMA_2 => [
    'name' => totranslate('Company of the second automa'),
    'values' => [
      RANDOM => [
        'name' => clienttranslate('Random'),
      ],
      COMPANY_USA => [
        'name' => clienttranslate('USA'),
        'tmdisplay' => clienttranslate('[USA:AI 2]'),
      ],
      COMPANY_GERMANY => [
        'name' => clienttranslate('Germany'),
        'tmdisplay' => clienttranslate('[De:AI 2]'),
      ],
      COMPANY_ITALY => [
        'name' => clienttranslate('Italy'),
        'tmdisplay' => clienttranslate('[It:AI 2]'),
      ],
      COMPANY_FRANCE => [
        'name' => clienttranslate('France'),
        'tmdisplay' => clienttranslate('[Fr:AI 2]'),
      ],
      COMPANY_NETHERLANDS => [
        'name' => clienttranslate('Netherlands'),
        'tmdisplay' => clienttranslate('[NL:AI 2]'),
      ],
    ],
    'displaycondition' => [
      [
        'type' => 'otheroption',
        'id' => OPTION_AUTOMA,
        'value' => [2, 3, 4],
      ],
    ],
  ],
  OPTION_AUTOMA_3 => [
    'name' => totranslate('Company of the third automa'),
    'values' => [
      RANDOM => [
        'name' => clienttranslate('Random'),
      ],
      COMPANY_USA => [
        'name' => clienttranslate('USA'),
        'tmdisplay' => clienttranslate('[USA:AI 3]'),
      ],
      COMPANY_GERMANY => [
        'name' => clienttranslate('Germany'),
        'tmdisplay' => clienttranslate('[De:AI 3]'),
      ],
      COMPANY_ITALY => [
        'name' => clienttranslate('Italy'),
        'tmdisplay' => clienttranslate('[It:AI 3]'),
      ],
      COMPANY_FRANCE => [
        'name' => clienttranslate('France'),
        'tmdisplay' => clienttranslate('[Fr:AI 3]'),
      ],
      COMPANY_NETHERLANDS => [
        'name' => clienttranslate('Netherlands'),
        'tmdisplay' => clienttranslate('[NL:AI 3]'),
      ],
    ],
    'displaycondition' => [
      [
        'type' => 'otheroption',
        'id' => OPTION_AUTOMA,
        'value' => [3, 4],
      ],
    ],
  ],
  OPTION_AUTOMA_4 => [
    'name' => totranslate('Company of the fourth automa'),
    'values' => [
      RANDOM => [
        'name' => clienttranslate('Random'),
      ],
      COMPANY_USA => [
        'name' => clienttranslate('USA'),
        'tmdisplay' => clienttranslate('[USA:AI 4]'),
      ],
      COMPANY_GERMANY => [
        'name' => clienttranslate('Germany'),
        'tmdisplay' => clienttranslate('[De:AI 4]'),
      ],
      COMPANY_ITALY => [
        'name' => clienttranslate('Italy'),
        'tmdisplay' => clienttranslate('[It:AI 4]'),
      ],
      COMPANY_FRANCE => [
        'name' => clienttranslate('France'),
        'tmdisplay' => clienttranslate('[Fr:AI 4]'),
      ],
      COMPANY_NETHERLANDS => [
        'name' => clienttranslate('Netherlands'),
        'tmdisplay' => clienttranslate('[NL:AI 4]'),
      ],
    ],
    'displaycondition' => [
      [
        'type' => 'otheroption',
        'id' => OPTION_AUTOMA,
        'value' => [4],
      ],
    ],
  ],



  OPTION_LVL_AUTOMA_1 => [
    'name' => totranslate('Difficulty level of the first automa'),
    'values' => [
      AUTOMA_EASY => [
        'name' => clienttranslate('Easy'),
        'tmdisplay' => clienttranslate('[AI 1:Easy]'),
      ],
      AUTOMA_MEDIUM => [
        'name' => clienttranslate('Medium'),
        'tmdisplay' => clienttranslate('[AI 1:Medium]'),
      ],
      AUTOMA_HARD => [
        'name' => clienttranslate('Hard'),
        'tmdisplay' => clienttranslate('[AI 1:Hard]'),
      ],
    ],
    'displaycondition' => [
      [
        'type' => 'otheroption',
        'id' => OPTION_AUTOMA,
        'value' => [1, 2, 3, 4],
      ],
    ],
  ],
  OPTION_LVL_AUTOMA_2 => [
    'name' => totranslate('Difficulty level of the second automa'),
    'values' => [
      AUTOMA_EASY => [
        'name' => clienttranslate('Easy'),
        'tmdisplay' => clienttranslate('[AI 2:Easy]'),
      ],
      AUTOMA_MEDIUM => [
        'name' => clienttranslate('Medium'),
        'tmdisplay' => clienttranslate('[AI 2:Medium]'),
      ],
      AUTOMA_HARD => [
        'name' => clienttranslate('Hard'),
        'tmdisplay' => clienttranslate('[AI 2:Hard]'),
      ],
    ],
    'displaycondition' => [
      [
        'type' => 'otheroption',
        'id' => OPTION_AUTOMA,
        'value' => [2, 3, 4],
      ],
    ],
  ],
  OPTION_LVL_AUTOMA_3 => [
    'name' => totranslate('Difficulty level of the third automa'),
    'values' => [
      AUTOMA_EASY => [
        'name' => clienttranslate('Easy'),
        'tmdisplay' => clienttranslate('[AI 3:Easy]'),
      ],
      AUTOMA_MEDIUM => [
        'name' => clienttranslate('Medium'),
        'tmdisplay' => clienttranslate('[AI 3:Medium]'),
      ],
      AUTOMA_HARD => [
        'name' => clienttranslate('Hard'),
        'tmdisplay' => clienttranslate('[AI 3:Hard]'),
      ],
    ],
    'displaycondition' => [
      [
        'type' => 'otheroption',
        'id' => OPTION_AUTOMA,
        'value' => [3, 4],
      ],
    ],
  ],
  OPTION_LVL_AUTOMA_4 => [
    'name' => totranslate('Difficulty level of the fourth automa'),
    'values' => [
      AUTOMA_EASY => [
        'name' => clienttranslate('Easy'),
        'tmdisplay' => clienttranslate('[AI 4:Easy]'),
      ],
      AUTOMA_MEDIUM => [
        'name' => clienttranslate('Medium'),
        'tmdisplay' => clienttranslate('[AI 4:Medium]'),
      ],
      AUTOMA_HARD => [
        'name' => clienttranslate('Hard'),
        'tmdisplay' => clienttranslate('[AI 4:Hard]'),
      ],
    ],
    'displaycondition' => [
      [
        'type' => 'otheroption',
        'id' => OPTION_AUTOMA,
        'value' => [4],
      ],
    ],
  ],
];

$game_preferences = [];
