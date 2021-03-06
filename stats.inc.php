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
 * stats.inc.php
 *
 * Barrage game statistics description
 *
 */

require_once 'modules/php/constants.inc.php';

$stats_type = [
  'table' => [
    'round1Obj' => [
      'id' => STAT_ROUND_1_OBJ,
      'name' => 'Round 1 Bonus Tile',
      'type' => 'int',
    ],
    'round2Obj' => [
      'id' => STAT_ROUND_2_OBJ,
      'name' => 'Round 2 Bonus Tile',
      'type' => 'int',
    ],
    'round3Obj' => [
      'id' => STAT_ROUND_3_OBJ,
      'name' => 'Round 3 Bonus Tile',
      'type' => 'int',
    ],
    'round4Obj' => [
      'id' => STAT_ROUND_4_OBJ,
      'name' => 'Round 4 Bonus Tile',
      'type' => 'int',
    ],
    'round5Obj' => [
      'id' => STAT_ROUND_5_OBJ,
      'name' => 'Round 5 Bonus Tile',
      'type' => 'int',
    ],
    'finalObj' => [
      'id' => STAT_FINAL_OBJ,
      'name' => 'End of Game Objective',
      'type' => 'int',
    ],
  ],
  'value_labels' => [
    STAT_ROUND_1_OBJ => [
      0 => totranslate('Fulfilled Contracts'),
      1 => totranslate('Built Bases'),
      2 => totranslate('Built Elevations'),
      3 => totranslate('Built Conduits'),
      4 => totranslate('Built Powerhouses'),
      5 => totranslate('Acquired Advanced Technology Tiles'),
      6 => totranslate('Fulfilled External Works'),
      7 => totranslate('Built Buildings'),
    ],
    STAT_ROUND_2_OBJ => [
      0 => totranslate('Fulfilled Contracts'),
      1 => totranslate('Built Bases'),
      2 => totranslate('Built Elevations'),
      3 => totranslate('Built Conduits'),
      4 => totranslate('Built Powerhouses'),
      5 => totranslate('Acquired Advanced Technology Tiles'),
      6 => totranslate('Fulfilled External Works'),
      7 => totranslate('Built Buildings'),
    ],
    STAT_ROUND_3_OBJ => [
      0 => totranslate('Fulfilled Contracts'),
      1 => totranslate('Built Bases'),
      2 => totranslate('Built Elevations'),
      3 => totranslate('Built Conduits'),
      4 => totranslate('Built Powerhouses'),
      5 => totranslate('Acquired Advanced Technology Tiles'),
      6 => totranslate('Fulfilled External Works'),
      7 => totranslate('Built Buildings'),
    ],
    STAT_ROUND_4_OBJ => [
      0 => totranslate('Fulfilled Contracts'),
      1 => totranslate('Built Bases'),
      2 => totranslate('Built Elevations'),
      3 => totranslate('Built Conduits'),
      4 => totranslate('Built Powerhouses'),
      5 => totranslate('Acquired Advanced Technology Tiles'),
      6 => totranslate('Fulfilled External Works'),
      7 => totranslate('Built Buildings'),
    ],
    STAT_ROUND_5_OBJ => [
      0 => totranslate('Fulfilled Contracts'),
      1 => totranslate('Built Bases'),
      2 => totranslate('Built Elevations'),
      3 => totranslate('Built Conduits'),
      4 => totranslate('Built Powerhouses'),
      5 => totranslate('Acquired Advanced Technology Tiles'),
      6 => totranslate('Fulfilled External Works'),
      7 => totranslate('Built Buildings'),
    ],
    STAT_FINAL_OBJ => [
      1 => totranslate('Costly construction slots'),
      2 => totranslate('Area with the most structure pieces'),
      3 => totranslate('Connected production systems'),
      4 => totranslate('Area with the least structure pieces'),
      5 => totranslate('Basins with at least one structure piece'),
      6 => totranslate('Basins with at least three structure piece'),
    ],
    STAT_POSITION => [
      1 => totranslate('First player'),
      2 => totranslate('Second player'),
      3 => totranslate('Third player'),
      4 => totranslate('Fourth player'),
    ],
    STAT_NATION => [
      1 => totranslate('USA'),
      2 => totranslate('Germany'),
      3 => totranslate('Italy'),
      4 => totranslate('France'),
      5 => totranslate('Netherlands'),
    ],
    STAT_XO => [
      1 => totranslate('Wilhelm Adler'),
      2 => totranslate('Elon Audia'),
      3 => totranslate('Tommaso Battista'),
      4 => totranslate('Graziano Del Monte'),
      5 => totranslate('Viktor Fiesler'),
      6 => totranslate('Margot Fouche'),
      7 => totranslate('Gennaro Grasso'),
      8 => totranslate('Solomon P. Jordan'),
      9 => totranslate('Anton Krylov'),
      10 => totranslate('Simone Luciani'),
      11 => totranslate('Jill McDowell'),
      12 => totranslate('Mahiri Sekibo'),
      13 => totranslate('Leslie Spencer'),
      14 => totranslate('Wu Fang'),
      15 => totranslate('Dr. Octavius'),
      16 => totranslate('Amir Zahir'),
    ],
  ],

  'player' => [
    'position' => [
      'id' => STAT_POSITION,
      'name' => totranslate('Starting position in first round'),
      'type' => 'int',
    ],
    'nation' => [
      'id' => STAT_NATION,
      'name' => totranslate('Nation'),
      'type' => 'int',
    ],
    'officer' => [
      'id' => STAT_XO,
      'name' => totranslate('Executive Officer'),
      'type' => 'int',
    ],
    'energy' => [
      'id' => STAT_ENERGY,
      'name' => totranslate('Total Energy Produced'),
      'type' => 'int',
    ],
    'base' => [
      'id' => STAT_BUILT_BASES,
      'name' => totranslate('Built Bases'),
      'type' => 'int',
    ],
    'elevation' => [
      'id' => STAT_BUILT_ELEVATION,
      'name' => totranslate('Built Elevations'),
      'type' => 'int',
    ],
    'conduit' => [
      'id' => STAT_BUILT_CONDUIT,
      'name' => totranslate('Built Conduits'),
      'type' => 'int',
    ],
    'powerhouse' => [
      'id' => STAT_BUILT_POWERHOUSE,
      'name' => totranslate('Built Powerhouses'),
      'type' => 'int',
    ],
    'contract' => [
      'id' => STAT_FULFILLED_CONTRACTS,
      'name' => totranslate('Fulfilled Contracts'),
      'type' => 'int',
    ],
    'advTile' => [
      'id' => STAT_ADVANCED_TILES,
      'name' => totranslate('Acquired Advanced Technology Tiles'),
      'type' => 'int',
    ],
    'round1Energy' => [
      'id' => STAT_ENERGY_ROUND_1,
      'name' => totranslate('Energy produced in round 1'),
      'type' => 'int',
    ],
    'round1VP' => [
      'id' => STAT_VP_ROUND_1,
      'name' => totranslate('Round 1 Bonus Tile Victory Points'),
      'type' => 'int',
    ],
    'round2Energy' => [
      'id' => STAT_ENERGY_ROUND_2,
      'name' => totranslate('Energy produced in round 2'),
      'type' => 'int',
    ],
    'round2VP' => [
      'id' => STAT_VP_ROUND_2,
      'name' => totranslate('Round 2 Bonus Tile Victory Points'),
      'type' => 'int',
    ],
    'round3Energy' => [
      'id' => STAT_ENERGY_ROUND_3,
      'name' => totranslate('Energy produced in round 3'),
      'type' => 'int',
    ],
    'round3VP' => [
      'id' => STAT_VP_ROUND_3,
      'name' => totranslate('Round 3 Bonus Tile Victory Points'),
      'type' => 'int',
    ],
    'round4Energy' => [
      'id' => STAT_ENERGY_ROUND_4,
      'name' => totranslate('Energy produced in round 4'),
      'type' => 'int',
    ],
    'round4VP' => [
      'id' => STAT_VP_ROUND_4,
      'name' => totranslate('Round 4 Bonus Tile Victory Points'),
      'type' => 'int',
    ],
    'round5Energy' => [
      'id' => STAT_ENERGY_ROUND_5,
      'name' => totranslate('Energy produced in round 5'),
      'type' => 'int',
    ],
    'round5VP' => [
      'id' => STAT_VP_ROUND_5,
      'name' => totranslate('Round 5 Bonus Tile Victory Points'),
      'type' => 'int',
    ],
    'objCount' => [
      'id' => STAT_FINAL_OBJ_COUNT,
      'name' => totranslate('Number of "things" taken into account for end of game objective'),
      'type' => 'int',
    ],
    'objVp' => [
      'id' => STAT_FINAL_OBJ_VP,
      'name' => totranslate('Victory points for end of game objective'),
      'type' => 'int',
    ],
  ],
];
