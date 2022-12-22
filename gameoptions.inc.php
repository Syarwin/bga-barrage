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

require_once 'modules/php/gameoptions.inc.php';

$game_options = [
  OPTION_SETUP => [
    'name' => totranslate('Setup'),
    'values' => [
      OPTION_SETUP_BEGINNER => [
        'name' => totranslate('Introductory game'),
        'tmdisplay' => totranslate('[Introductory]'),
      ],
      OPTION_SETUP_STANDARD => [
        'name' => totranslate('Standard'),
        'nobeginner' => true,
      ],
      /*
      OPTION_SETUP_FREE => [
        'name' => totranslate('Free setup'),
        'tmdisplay' => totranslate('Choose freely among the available companies and officers'),
        'nobeginner' => true,
      ],
      OPTION_SETUP_SEED => [
        'name' => totranslate('Seed mode - replay a game'),
        'tmdisplay' => totranslate(
          'Use the same setup as in an already finished game - YOU NEED A SEED TO PLAY THAT MODE'
        ),
        'nobeginner' => true,
      ],
*/
    ],
  ],

  OPTION_EXPANSION_LWP => [
    'name' => totranslate('The Leeghwater Project expansion'),
    'values' => [
      OPTION_EXPANSION_LWP_OFF => [
        'name' => totranslate('Disabled'),
      ],
      OPTION_EXPANSION_LWP_ON => [
        'name' => totranslate('Enabled'),
        'tmdisplay' => totranslate('[LWP]'),
        'nobeginner' => true,
        'alpha' => true,
      ],
    ],
    'displaycondition' => [
      [
        'type' => 'otheroptionisnot',
        'id' => OPTION_SETUP,
        'value' => [OPTION_SETUP_BEGINNER],
      ],
    ],
  ],

  OPTION_NEW_XO => [
    'name' => totranslate('New officers (Simone and Tommaso)'),
    'values' => [
      OPTION_NEW_XO_OFF => [
        'name' => totranslate('Excluded'),
      ],
      OPTION_NEW_XO_ON => [
        'name' => totranslate('Included'),
        'tmdisplay' => totranslate('[New XO]'),
      ],
    ],
    'displaycondition' => [
      [
        'type' => 'otheroptionisnot',
        'id' => OPTION_SETUP,
        'value' => [OPTION_SETUP_BEGINNER],
      ],
    ],
  ],

  OPTION_MAP => [
    'name' => totranslate('4/5p expansion map'),
    'values' => [
      0 => [
        'name' => totranslate('Disabled'),
      ],
      1 => [
        'name' => totranslate('Enabled'),
        'tmdisplay' => totranslate('[4/5p map]'),
        'nobeginner' => true,
      ],
    ],
    'displaycondition' => [
      [
        'type' => 'minplayers',
        'value' => 4,
      ],
    ],
  ],

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
        'alpha' => true,
      ],
      2 => [
        'name' => clienttranslate('2 automas'),
        'tmdisplay' => clienttranslate('[2 automas]'),
      ],
      3 => [
        'name' => clienttranslate('3 automas'),
        'tmdisplay' => clienttranslate('[3 automas]'),
      ],
      // 4 => [
      //   'name' => clienttranslate('4 automas'),
      //   'tmdisplay' => clienttranslate('[4 automas]'),
      // ],
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
          'value' => 3,
          'message' => clienttranslate('Number of automas + players can\'t exceed 4'),
        ],
      ],
      2 => [
        [
          'type' => 'maxplayers',
          'value' => 2,
          'message' => clienttranslate('Number of automas + players can\'t exceed 4'),
        ],
      ],
      3 => [
        [
          'type' => 'maxplayers',
          'value' => 1,
          'message' => clienttranslate('Number of automas + players can\'t exceed 4'),
        ],
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
      // AUTOMA_HARD => [
      //   'name' => clienttranslate('Hard'),
      //   'tmdisplay' => clienttranslate('[AI 1:Hard]'),
      // ],
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
      // AUTOMA_HARD => [
      //   'name' => clienttranslate('Hard'),
      //   'tmdisplay' => clienttranslate('[AI 2:Hard]'),
      // ],
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
      // AUTOMA_HARD => [
      //   'name' => clienttranslate('Hard'),
      //   'tmdisplay' => clienttranslate('[AI 3:Hard]'),
      // ],
    ],
    'displaycondition' => [
      [
        'type' => 'otheroption',
        'id' => OPTION_AUTOMA,
        'value' => [3, 4],
      ],
    ],
  ],
  // OPTION_LVL_AUTOMA_4 => [
  //   'name' => totranslate('Difficulty level of the fourth automa'),
  //   'values' => [
  //     AUTOMA_EASY => [
  //       'name' => clienttranslate('Easy'),
  //       'tmdisplay' => clienttranslate('[AI 4:Easy]'),
  //     ],
  //     AUTOMA_MEDIUM => [
  //       'name' => clienttranslate('Medium'),
  //       'tmdisplay' => clienttranslate('[AI 4:Medium]'),
  //     ],
  //     AUTOMA_HARD => [
  //       'name' => clienttranslate('Hard'),
  //       'tmdisplay' => clienttranslate('[AI 4:Hard]'),
  //     ],
  //   ],
  //   'displaycondition' => [
  //     [
  //       'type' => 'otheroption',
  //       'id' => OPTION_AUTOMA,
  //       'value' => [4],
  //     ],
  //   ],
  // ],
];

$game_preferences = [
  OPTION_CONFIRM => [
    'name' => totranslate('Turn confirmation'),
    'needReload' => false,
    'values' => [
      OPTION_CONFIRM_TIMER => ['name' => totranslate('Enabled with timer')],
      OPTION_CONFIRM_ENABLED => ['name' => totranslate('Enabled')],
      OPTION_CONFIRM_DISABLED => ['name' => totranslate('Disabled')],
    ],
  ],
];
