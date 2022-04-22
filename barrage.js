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

      this.setupActionBoards();
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
        let o = this.place('tplConduitSlot', { cId }, oMap);
        o.addEventListener('mouseenter', () => {
          dojo.query(`.powerhouse-slot[data-zone="${map.conduits[cId].end}"]`).addClass('highlight');
        });
        o.addEventListener('mouseleave', () => {
          dojo.query('.powerhouse-slot.highlight').removeClass('highlight');
        });
      });

      // Powerhouses
      map.powerhouses.forEach((powerhouse) => {
        this.place('tplPowerhouseSlot', powerhouse, oMap);
      });

      // Basins
      map.basins.forEach((basin) => {
        this.place('tplBasin', basin, oMap);
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

    tplPowerhouseSlot(powerhouse) {
      let cost = powerhouse.cost > 0 ? 'paying' : '';
      return `<div class='powerhouse-slot ${cost}' data-zone="${powerhouse.zone}" data-id='${powerhouse.id}'></div>`;
    },

    tplBasin(basin) {
      let cost = basin.cost > 0 ? 'paying' : '';
      return `<div class='basin' data-id='${basin.id}'></div>
        <div class='dam-slot ${cost}' data-id='${basin.id}'></div>`;
    },

    ////////////////////////////////////////////////////////////////////////
    //     _        _   _               ____                      _
    //    / \   ___| |_(_) ___  _ __   | __ )  ___   __ _ _ __ __| |___
    //   / _ \ / __| __| |/ _ \| '_ \  |  _ \ / _ \ / _` | '__/ _` / __|
    //  / ___ \ (__| |_| | (_) | | | | | |_) | (_) | (_| | | | (_| \__ \
    // /_/   \_\___|\__|_|\___/|_| |_| |____/ \___/ \__,_|_|  \__,_|___/
    //
    ////////////////////////////////////////////////////////////////////////
    setupActionBoards() {
      this.gamedatas.actionBoards.forEach((board) => {
        if (board.id == 'company') {
        } else {
          this.place('tplActionBoard', board, 'barrage-container');
        }
      });
    },

    tplActionBoard(board) {
      let structure = board.structure.map((row) => this.tplActionBoardRow(row));

      return `<div class='action-board barrage-frame' data-id='${board.id}'>
        <div class='action-board-name-container'>
          <div class='action-board-name'>${_(board.name)}</div>
        </div>
        <div class='action-board-inner'>
          ${structure.join('')}
        </div>
      </div>`;
    },

    tplActionBoardRow(row) {
      let slots = row.map((slot) => {
        if (typeof slot === 'string') {
          return `<div class="action-board-icon">${this.formatString(slot)}</div>`;
        } else {
          return this.tplActionSpace(slot);
        }
      });

      return `<div class='action-board-row'>
        ${slots.join('')}
      </div>`;
    },

    tplActionSpace(space) {
      let slots = [];
      for (let i = 0; i < space.nEngineers; i++) {
        slots.push(`<div class="action-space-slot" data-id='${space.uid}-${i}'></div>`);
      }
      let cost = '';
      if (space.cost > 0) {
        cost = '<div class="action-space-cost">' + this.formatString(`<COST:${space.cost}>`) + '</div>';
      }

      return `<div class='action-space ${space.cost > 0 ? 'paying' : ''}' data-id='${space.uid}'>
        ${slots.join('')}
        ${cost}
      </div>`;
    },
  });
});
