<?php
require_once("gameoptions.inc.php");

/*
 * Game options
 */

/*
 * User preferences
 */

/*
 * State constants
 */
const ST_GAME_SETUP = 1;

const ST_FOO = 2;


const ST_BEFORE_START_OF_TURN = 4;
const ST_PREPARATION = 5;
const ST_NEXT_PLAYER_LABOR = 6;
const ST_LABOR = 7;
const ST_RETURNING_HOME = 8;
const ST_RESOLVE_STACK = 10;
const ST_RESOLVE_CHOICE = 11;


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



const ST_PRE_END_OF_TURN = 40;
const ST_END_OF_TURN = 9;

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


/*
 * Companies
 */
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
 * Types of ressources
 */
const CREDIT = 'credit';
const EXCAVATOR = 'excavator';
const MIXER = 'mixer';

const MACHINERIES = [EXCAVATOR, MIXER];
const RESOURCES = [CREDIT, EXCAVATOR, MIXER];

const DROPLET = 'droplet';

const BASE = 'base';
const ELEVATION = 'elevation';
const CONDUIT = 'conduit';
const POWERHOUSE = 'powerhouse';
const ENGINEER = 'engineer';

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


/** ExtraDatas**/
const BONUS_VP = 'bonusVP';

/******************
 ****** STATS ******
 ******************/
