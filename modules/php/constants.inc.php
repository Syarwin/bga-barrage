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

const ST_AUCTION_NEXT_PLAYER = 6;
const ST_AUCTION_PLACE_BET = 7;

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
const ST_PRODUCE = 24;
const ST_FULFILL_CONTRACT = 25;
const ST_PLACE_DROPLET = 26;
const ST_ROTATE_WHEEL = 27;
const ST_TAKE_CONTRACT = 28;
const ST_SPECIAL_EFFECT = 29;
const ST_DISCARD_CONTRACT = 30;
const ST_PLACE_STRUCTURE = 31;
const ST_TILE_EFFECT = 32;
const ST_PATENT = 33;
const ST_EXTERNAL_WORK = 34;
const ST_RETRIEVE_FROM_WHEEL = 35;

const ST_PRE_AUTOMA_TURN = 40;
const ST_AUTOMA_TURN = 41;

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
const WHOLE_COST = 'whole_cost';

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

const CONTRACT_GREEN = 2;
const CONTRACT_YELLOW = 3;
const CONTRACT_RED = 4;

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
const XO_OCTAVIUS = 15;
const XO_AMIR = 16;

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
const EXCAMIXER = 'excamixer';
const DROPLET = 'droplet';
const SCORE = 'score';
const ENERGY = 'energy';
const VP = 'vp';
const ENGINEER = 'engineer';
const ARCHITECT = 'architect';
const N_ARCHITECT = -1;

const MACHINERIES = [EXCAVATOR, MIXER];
const RESOURCES = [CREDIT, EXCAVATOR, MIXER, DROPLET, VP, ENERGY, ENGINEER, ARCHITECT];

const TECH_TILE = 'technology_tile';

const BASE = 'base';
const ELEVATION = 'elevation';
const CONDUIT = 'conduit';
const POWERHOUSE = 'powerhouse';
const BUILDING = 'building';
const JOKER = 'joker';
const ANTON_TILE = 'anton';
const L1_BASE = 'L1_BASE';
const L1_ELEVATION = 'L1_ELEVATION';
const L1_CONDUIT = 'L1_CONDUIT';
const L1_POWERHOUSE = 'L1_POWERHOUSE';
const L1_JOKER = 'L1_JOKER';
const L1_BUILDING = 'L1_BUILDING';
const L2_BASE = 'L2_BASE';
const L2_ELEVATION = 'L2_ELEVATION';
const L2_CONDUIT = 'L2_CONDUIT';
const L2_POWERHOUSE = 'L2_POWERHOUSE';
const L2_JOKER = 'L2_JOKER';
const L2_BUILDING = 'L2_BUILDING';
const L3_BASE = 'L3_BASE';
const L3_ELEVATION = 'L3_ELEVATION';
const L3_CONDUIT = 'L3_CONDUIT';
const L3_POWERHOUSE = 'L3_POWERHOUSE';
const L3_JOKER = 'L3_JOKER';
const L3_BUILDING = 'L3_BUILDING';

const STRUCTURES = [BASE, ELEVATION, CONDUIT, POWERHOUSE, BUILDING];

const BASIC_TILES = [BASE, ELEVATION, CONDUIT, POWERHOUSE, JOKER, BUILDING];
const L1_TILES = [L1_BASE, L1_ELEVATION, L1_CONDUIT, L1_POWERHOUSE, L1_JOKER];
const L2_TILES = [L2_BASE, L2_ELEVATION, L2_CONDUIT, L2_POWERHOUSE, L2_JOKER];
const L3_TILES = [L3_BASE, L3_ELEVATION, L3_CONDUIT, L3_POWERHOUSE, L3_JOKER];

// Useful for contracts/incomes
const ANY_MACHINE = 'any_machine';
const FLOW_DROPLET = 'flow_droplet';
const PRODUCTION_BONUS = 'production_bonus';
const ADVANCED_TECH_TILE = 'advanced_tech_tile';
const ENERGY_PRODUCED = 'energy_produced';

const SPECIAL_POWER = 'special_power';
const SPECIAL_POWER_USA = 'special_power_usa';
const SPECIAL_POWER_ITALY = 'special_power_italy';
const SPECIAL_POWER_FRANCE = 'special_power_france';
const SPECIAL_POWER_GERMANY = 'special_power_germany';
const SPECIAL_POWER_NETHERLANDS = 'special_power_netherlands';

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
const BOARD_PATENT = 'patent';
const BOARD_OFFICER = 'officer';
const BOARD_EXTERNAL_WORK = 'externalWork';
const BOARD_BUILDINGS = 'buildings';

/*
 * Atomic action
 */
const PLACE_ENGINEER = 'PLACE_ENGINEER';
const GAIN = 'GAIN';
const CONSTRUCT = 'CONSTRUCT';
const PAY = 'PAY';
const PRODUCE = 'PRODUCE';
const FULFILL_CONTRACT = 'FULFILL_CONTRACT';
const PLACE_DROPLET = 'PLACE_DROPLET';
const ROTATE_WHEEL = 'ROTATE_WHEEL';
const TAKE_CONTRACT = 'TAKE_CONTRACT';
const SPECIAL_EFFECT = 'SPECIAL_EFFECT';
const DISCARD_CONTRACTS = 'DISCARD_CONTRACTS';
const PLACE_STRUCTURE = 'PLACE_STRUCTURE';
const TILE_EFFECT = 'TILE_EFFECT';
const PATENT = 'PATENT';

const EXTERNAL_WORK = 'EXTERNAL_WORK';

// LWP
const RETRIEVE_FROM_WHEEL = 'RETRIEVE_FROM_WHEEL';

// AUTOMA ACTION
const GAIN_MACHINE = 'GAIN_MACHINE';
const GAIN_VP = 'GAIN_VP';

const NOT_LAST_ROUND = 'not_last_round';

// AUTOMA CRITERION
const AI_CRITERION_BASE_MAX_CONDUIT = 'criterionBaseConduit';
const AI_CRITERION_BASE_POWERHOUSE = 'criterionBasePowerhouse';
const AI_CRITERION_BASE_HOLD_WATER = 'criterionBaseHoldWater';
const AI_CRITERION_BASE_PAYING_SLOT = 'criterionBasePayingSlot';
const AI_CRITERION_BASE_POWERHOUSE_WATER = 'criterionBasePowerhouseWater';
const AI_CRITERION_BASE_BASIN = 'criterionBaseBasin';

const AI_CRITERION_CONDUIT_HIGHEST = 'criterionConduitHighest';
const AI_CRITERION_CONDUIT_SECOND_HIGHEST = 'criterionConduitSecondHighest';
const AI_CRITERION_CONDUIT_BARRAGE = 'criterionConduitBarrage';
const AI_CRITERION_CONDUIT_POWERHOUSE = 'criterionConduitPowerhouse';
const AI_CRITERION_CONDUIT_BARRAGE_REVERSE = 'criterionConduitBarrageReverse';
const AI_CRITERION_CONDUIT_POWERHOUSE_REVERSE = 'criterionConduitPowerhouseReverse';

const AI_CRITERION_POWERHOUSE_CONDUIT = 'criterionPowerhouseConduit';
const AI_CRITERION_POWERHOUSE_BARRAGE = 'criterionPowerhouseBarrage';
const AI_CRITERION_POWERHOUSE_PLAIN = 'criterionPowerhousePlain';
const AI_CRITERION_POWERHOUSE_HILL_5 = 'criterionPowerhouseHill5';
const AI_CRITERION_POWERHOUSE_HILL_6 = 'criterionPowerhouseHill6';
const AI_CRITERION_POWERHOUSE_HILL_7 = 'criterionPowerhouseHill7';
const AI_CRITERION_POWERHOUSE_BARRAGE_WATER = 'criterionPowerhouseBarrageWater';
const AI_CRITERION_POWERHOUSE_BARRAGE_WATER_REVERSE = 'criterionPowerhouseBarrageWaterReverse';

const AI_REVERSE_CRITERIA = [
  AI_CRITERION_CONDUIT_BARRAGE_REVERSE,
  AI_CRITERION_CONDUIT_POWERHOUSE_REVERSE,
  AI_CRITERION_POWERHOUSE_BARRAGE_WATER_REVERSE,
];

/** ExtraDatas**/
const BONUS_VP = 'bonusVP';

const wheelSlots = [0, 1, 2, 3, 4, 5];

/******************
 ****** STATS ******
 ******************/
const STAT_ROUND_1_OBJ = 200;
const STAT_ROUND_2_OBJ = 201;
const STAT_ROUND_3_OBJ = 202;
const STAT_ROUND_4_OBJ = 203;
const STAT_ROUND_5_OBJ = 204;
const STAT_FINAL_OBJ = 205;

const STAT_POSITION = 10;
const STAT_NATION = 11;
const STAT_XO = 12;
const STAT_ENERGY = 13;
const STAT_BUILT_BASES = 14;
const STAT_BUILT_ELEVATION = 15;
const STAT_BUILT_CONDUIT = 16;
const STAT_BUILT_POWERHOUSE = 17;
const STAT_FULFILLED_CONTRACTS = 18;
const STAT_ADVANCED_TILES = 19;
const STAT_ENERGY_ROUND_1 = 20;
const STAT_VP_ROUND_1 = 21;
const STAT_ENERGY_ROUND_2 = 22;
const STAT_VP_ROUND_2 = 23;
const STAT_ENERGY_ROUND_3 = 24;
const STAT_VP_ROUND_3 = 25;
const STAT_ENERGY_ROUND_4 = 26;
const STAT_VP_ROUND_4 = 27;
const STAT_ENERGY_ROUND_5 = 28;
const STAT_VP_ROUND_5 = 29;
const STAT_FINAL_OBJ_COUNT = 30;
const STAT_FINAL_OBJ_VP = 31;

const STAT_BUILT_BUILDING = 35;
const STAT_FULFILLED_EXTERNAL_WORKS = 32;
const STAT_VP_EXTERNAL_WORKS = 33;
const STAT_VP_ADVANCED_TILES = 34;
const STAT_VP_BUILDINGS = 36;

const STAT_VP_START = 40;
const STAT_VP_ENERGY_TRACK = 41;
const STAT_VP_STRUCTURES = 42;
const STAT_VP_CONTRACTS = 43;
const STAT_VP_CONDUIT = 44;
const STAT_VP_OBJ_TILE = 45;
const STAT_VP_WATER = 46;
const STAT_VP_RESOURCES = 47;
const STAT_VP_TOTAL = 48;
const STAT_VP_ENERGY_TRACK_BONUS = 49;

const STAT_VP_AUCTION = 50;
