/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Barrage implementation : © Timothe Pecatte <tim.pecatte@gmail.com>, Vincent Toper <vincent.toper@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * barrage.js
 *
 * Barrage user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
var debug = isDebug ? console.info.bind(window.console) : function () {};

define([
  'dojo',
  'dojo/_base/declare',
  'ebg/core/gamegui',
  'ebg/counter',
  g_gamethemeurl + 'modules/js/Core/game.js',
  g_gamethemeurl + 'modules/js/Core/modal.js',
  g_gamethemeurl + 'modules/js/Companies.js',
  g_gamethemeurl + 'modules/js/Meeples.js',
], function (dojo, declare) {
  return declare('bgagame.barrage', [customgame.game, barrage.companies, barrage.meeples], {
    constructor() {
      this._activeStates = [];
      this._notifications = [];

      // Fix mobile viewport (remove CSS zoom)
      this.default_viewport = 'width=900';
    },

    /**
     * Setup:
     *	This method set up the game user interface according to current game situation specified in parameters
     *	The method is called each time the game interface is displayed to a player, ie: when the game starts and when a player refreshes the game page (F5)
     *
     * Params :
     *	- mixed gamedatas : contains all datas retrieved by the getAllDatas PHP method.
     */
    setup(gamedatas) {
      debug('SETUP', gamedatas);
      this.inherited(arguments);

      this.setupCompanies();
      this.setupMeeples();

      this.setupMap();
    },

    /////////////////////////////
    //  __  __
    // |  \/  | __ _ _ __
    // | |\/| |/ _` | '_ \
    // | |  | | (_| | |_) |
    // |_|  |_|\__,_| .__/
    //              |_|
    /////////////////////////////

    setupMap() {
      let map = this.gamedatas.map;
      let oMap = dojo.place(`<div id='brg-map' data-map='${map.id}'></div>`, 'barrage-container');

      // Headstreams
      Object.keys(map.headstreams).forEach((hId) =>
        this.place('tplHeadstream', { hId, tileId: map.headstreams[hId] }, oMap),
      );

      // Conduits
      Object.keys(map.conduits).forEach((cId) => {
        this.place('tplConduitSlot', { cId }, oMap);
        // TODO this.onClick()
      });
    },

    tplHeadstream(headstream) {
      return `<div class='headstream' data-id='${headstream.hId}'>
        <div class='headstream-tile' data-tile='${headstream.tileId}'></div>
      </div>`;
    },

    tplConduitSlot(conduit) {
      return `<div class='conduit-slot' data-id='${conduit.cId}'></div>`;
    },
  });
});
