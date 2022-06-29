<?php

$gameinfos = [
  'game_name' => 'Barrage',
  'designer' => 'Tommaso Battista, Simone Luciani',
  'artist' => ' Mauro Alocci, Antonio De Luca, Roman Roland Kuteynikov',
  'year' => 2019,
  'publisher' => 'Cranio Creations',
  'publisher_website' => 'https://craniointernational.com/',
  'publisher_bgg_id' => 10768,
  'bgg_id' => 251247,
  'players' => [2, 3, 4],

  'suggest_player_number' => 4,
  'not_recommend_player_number' => 1,

  'estimated_duration' => 90,
  'fast_additional_time' => 30,
  'medium_additional_time' => 40,
  'slow_additional_time' => 50,

  // If you are using a tie breaker in your game (using "player_score_aux"), you must describe here
  // the formula used to compute "player_score_aux". This description will be used as a tooltip to explain
  // the tie breaker to the players.
  // Note: if you are NOT using any tie breaker, leave the empty string.
  //
  // Example: 'tie_breaker_description' => totranslate( "Number of remaining cards in hand" ),
  'tie_breaker_description' => '',

  // If in the game, all losers are equal (no score to rank them or explicit in the rules that losers are not ranked between them), set this to true
  // The game end result will display "Winner" for the 1st player and "Loser" for all other players
  'losers_not_ranked' => false,

  // Allow to rank solo games for games where it's the only available mode (ex: Thermopyles). Should be left to false for games where solo mode exists in addition to multiple players mode.
  'solo_mode_ranked' => false,

  // Game is "beta". A game MUST set is_beta=1 when published on BGA for the first time, and must remains like this until all bugs are fixed.
  'is_beta' => 1,
  'is_coop' => 0,
  'language_dependency' => false,

  // Complexity of the game, from 0 (extremely simple) to 5 (extremely complex)
  'complexity' => 3,
  'luck' => 2,
  'strategy' => 5,
  'diplomacy' => 1,

  'player_colors' => ['be2748', '13757e', 'ffffff', 'ea4e1b', '1b1b1b'],
  'favorite_colors_support' => false,
  'disable_player_order_swap_on_rematch' => false,

  // Game interface width range (pixels)
  // Note: game interface = space on the left side, without the column on the right
  'game_interface_width' => [
    'min' => 740,
    'max' => null,
  ],

  'presentation' => [
    totranslate(
      'Barrage is a resource management strategic game in which players compete to build their majestic dams, raise them to increase their storing capacity, and deliver all the potential power through pressure tunnels connected to the energy turbines of their powerhouses.'
    ),
    totranslate(
      'Each player represents one of the four international companies who are gathering machinery, innovative patents and brilliant engineers to claim the best locations to collect and exploit the water of a contested Alpine region crossed by rivers.'
    ),
  ],

  // Games categories
  //  You can attribute a maximum of FIVE "tags" for your game.
  //  Each tag has a specific ID (ex: 22 for the category "Prototype", 101 for the tag "Science-fiction theme game")
  //  Please see the "Game meta information" entry in the BGA Studio documentation for a full list of available tags:
  //  http://en.doc.boardgamearena.com/Game_meta-information:_gameinfos.inc.php
  //  IMPORTANT: this list should be ORDERED, with the most important tag first.
  //  IMPORTANT: it is mandatory that the FIRST tag is 1, 2, 3 and 4 (= game category)
  'tags' => [2],

  //////// BGA SANDBOX ONLY PARAMETERS (DO NOT MODIFY)
  // simple : A plays, B plays, C plays, A plays, B plays, ...
  // circuit : A plays and choose the next player C, C plays and choose the next player D, ...
  // complex : A+B+C plays and says that the next player is A+B
  'is_sandbox' => false,
  'turnControl' => 'simple',
  ////////
];
