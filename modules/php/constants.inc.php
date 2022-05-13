<?php
require_once 'gameoptions.inc.php';

/*
 * State constants
 */
const ST_GAME_SETUP = 1;

const ST_SETUP_BRANCH = 2;

const ST_PICK_START_NEXT = 3;
const ST_PICK_START = 4;
const ST_FREE_PICK_START = 5;

const ST_BEFORE_START_OF_ROUND = 10;
const ST_START_OF_ROUND = 11;
const ST_ACTION_PHASE = 12;
const ST_RESOLVE_STACK = 13;
const ST_RESOLVE_CHOICE = 14;

const ST_RETURNING_HOME = 17;
const ST_PRE_END_OF_TURN = 18;
const ST_END_OF_TURN = 19;

const ST_PLACE_ENGINEER = 20;
const ST_GAIN = 21;
const ST_CONSTRUCT = 22;
const ST_PAY = 23;
const ST_PRODUCT = 24;
const ST_FULFILL_CONTRACT = 25;
const ST_PLACE_DROPLET = 26;
const ST_ROTATE_WHEEL = 27;
const ST_TAKE_CONTRACT = 28;
const ST_SPECIAL_EFFECT = 29;
const ST_DISCARD_CONTRACT = 30;
const ST_PLACE_STRUCTURE = 31;

const ST_CONFIRM_TURN = 90;
const ST_CONFIRM_PARTIAL_TURN = 91;

const ST_LOAD_SEED = 96;
const ST_GENERIC_NEXT_PLAYER = 97;

const ST_PRE_END_OF_GAME = 98;
const ST_END_GAME = 99;

/*
 * ENGINE
 */
const NODE_SEQ = 'seq';
const NODE_OR = 'or';
const NODE_XOR = 'xor';
const NODE_PARALLEL = 'parallel';
const NODE_LEAF = 'leaf';

const ZOMBIE = 98;
const PASS = 99;

const INFTY = 9999;
const NO_COST = ['trades' => [['max' => 1]]];

/**
 * Map
 */
const MAP_BASE = 1;
const MAP_5P = 2;
const MAP_2P = 3;

const MOUNTAIN = 'mountain';
const HILL = 'hill';
const PLAIN = 'plain';
const AREAS = [MOUNTAIN, HILL, PLAIN];

// HeadstreamTiles
const HT_1 = 1;
const HT_2 = 2;
const HT_3 = 3;
const HT_4 = 4;
const HT_5 = 5;
const HT_6 = 6;
const HT_7 = 7;
const HT_8 = 8;

/*
 * Bonus tiles
 */
const BONUS_CONTRACT = 0;
const BONUS_BASE = 1;
const BONUS_ELEVATION = 2;
const BONUS_CONDUIT = 3;
const BONUS_POWERHOUSE = 4;
const BONUS_ADVANCED_TILE = 5;
const BONUS_EXTERNAL_WORK = 6;
const BONUS_BUILDING = 7;

/*
 * Objective tiles
 */
const OBJECTIVE_PAYING_SLOT = 1;
const OBJECTIVE_MOST_STRUCTURE = 2;
const OBJECTIVE_CONNECTIONS = 3;
const OBJECTIVE_LEAST_STRUCTURE = 4;
const OBJECTIVE_BASIN_ONE = 5;
const OBJECTIVE_BASIN_THREE = 6;
const OBJECTIVE_TILES = [
  OBJECTIVE_PAYING_SLOT,
  OBJECTIVE_MOST_STRUCTURE,
  OBJECTIVE_CONNECTIONS,
  OBJECTIVE_LEAST_STRUCTURE,
  OBJECTIVE_BASIN_ONE,
  OBJECTIVE_BASIN_THREE,
];

/*
 * Contracts
 */
//const STARTING_CONTRACTS = [1, 2, 3, 4, 5];
const STARTING_CONTRACTS = [1, 2, 3, 4];

/*
 * Companies
 */
const COMPANY_NEUTRAL = 0;
const COMPANY_USA = 1;
const COMPANY_GERMANY = 2;
const COMPANY_ITALY = 3;
const COMPANY_FRANCE = 4;
const COMPANY_NETHERLANDS = 5;

/*
 * Executive Officers
 */
const XO_WILHELM = 1;
const XO_ELON = 2;
const XO_TOMMASO = 3;
const XO_GRAZIANO = 4;
const XO_VIKTOR = 5;
const XO_MARGOT = 6;
const XO_GENNARO = 7;
const XO_SOLOMON = 8;
const XO_ANTON = 9;
const XO_SIMONE = 10;
const XO_JILL = 11;
const XO_MAHIRI = 12;
const XO_LESLIE = 13;
const XO_WU = 14;
const XO_OCTAVIUS = 14;
const XO_AMIR = 15;

/*
 * Introductory Matchup
 */
const INTRODUCTORY_MATCHUPS = [
  [COMPANY_USA, XO_WILHELM, 1],
  [COMPANY_GERMANY, XO_JILL, 2],
  [COMPANY_ITALY, XO_SOLOMON, 3],
  [COMPANY_FRANCE, XO_VIKTOR, 4],
];

/*
 * Types of ressources
 */
const CREDIT = 'credit';
const EXCAVATOR = 'excavator';
const MIXER = 'mixer';
const DROPLET = 'droplet';
const SCORE = 'score';
const ENERGY = 'energy';
const VP = 'vp';

const MACHINERIES = [EXCAVATOR, MIXER];
const RESOURCES = [CREDIT, EXCAVATOR, MIXER, DROPLET, VP, ENERGY];

const BASE = 'base';
const ELEVATION = 'elevation';
const CONDUIT = 'conduit';
const POWERHOUSE = 'powerhouse';
const JOKER = 'joker';

const ENGINEER = 'engineer';
const ARCHITECT = 'architect';
const N_ARCHITECT = -1;

const BASIC_TILES = [BASE, ELEVATION, CONDUIT, POWERHOUSE, JOKER];

// Useful for contracts
const ANY_MACHINE = 'any_machine';
const FLOW_DROPLET = 'flow_droplet';

/*
 * Boards
 */
const BOARD_COMPANY = 'company';
const BOARD_TURBINE = 'turbine';
const BOARD_WATER = 'water';
const BOARD_BANK = 'bank';
const BOARD_WORSKHOP = 'workshop';
const BOARD_MACHINERY_SHOP = 'machinery';
const BOARD_CONTRACT = 'contract';

/*
 * Atomic action
 */
const PLACE_ENGINEER = 'PLACE_ENGINEER';
const GAIN = 'GAIN';
const CONSTRUCT = 'CONSTRUCT';
const PAY = 'PAY';
const PRODUCT = 'PRODUCT';
const FULFILL_CONTRACT = 'FULFILL_CONTRACT';
const PLACE_DROPLET = 'PLACE_DROPLET';
const ROTATE_WHEEL = 'ROTATE_WHEEL';
const TAKE_CONTRACT = 'TAKE_CONTRACT';
const SPECIAL_EFFECT = 'SPECIAL_EFFECT';
const DISCARD_CONTRACTS = 'DISCARD_CONTRACTS';
const PLACE_STRUCTURE = 'PLACE_STRUCTURE';

/** ExtraDatas**/
const BONUS_VP = 'bonusVP';

const wheelSlots = [0, 1, 2, 3, 4, 5];
/******************
 ****** STATS ******
 ******************/
