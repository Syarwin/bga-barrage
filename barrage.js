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

      this._settingsConfig = {
        layout: {
          default: 0,
          name: _('Layout'),
          attribute: 'layout',
          type: 'select',
          values: {
            0: _('Compact'),
            1: _('Standard'),
          },
        },
      };
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
      this.setupInfoPanel();

      this.setupActionBoards();
      this.setupCompanies();
      this.setupMeeples();

      this.setupMap();

      this.inherited(arguments);
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
        if (slot['i'] != undefined) {
          let id = this.registerCustomTooltip(_(slot.t));
          return `<div id="${id}" class="action-board-icon">${this.formatString(slot.i)}</div>`;
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

    //////////////////////////////////////////////////
    //  ____       _   _   _
    // / ___|  ___| |_| |_(_)_ __   __ _ ___
    // \___ \ / _ \ __| __| | '_ \ / _` / __|
    //  ___) |  __/ |_| |_| | | | | (_| \__ \
    // |____/ \___|\__|\__|_|_| |_|\__, |___/
    //                             |___/
    //////////////////////////////////////////////////

    setupInfoPanel() {
      dojo.place(this.tplConfigPlayerBoard(), 'player_boards', 'first');

      let chk = $('help-mode-chk');
      dojo.connect(chk, 'onchange', () => this.toggleHelpMode(chk.checked));
      this.addTooltip('help-mode-switch', '', _('Toggle help/safe mode.'));
    },

    tplConfigPlayerBoard() {
      return `
<div class='player-board' id="player_board_config">
  <div id="player_config" class="player_board_content">

    <div class="player_config_row">
      <div id="help-mode-switch">
        <input type="checkbox" class="checkbox" id="help-mode-chk" />
        <label class="label" for="help-mode-chk">
          <div class="ball"></div>
        </label>

        <svg aria-hidden="true" focusable="false" data-prefix="fad" data-icon="question-circle" class="svg-inline--fa fa-question-circle fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><g class="fa-group"><path class="fa-secondary" fill="currentColor" d="M256 8C119 8 8 119.08 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm0 422a46 46 0 1 1 46-46 46.05 46.05 0 0 1-46 46zm40-131.33V300a12 12 0 0 1-12 12h-56a12 12 0 0 1-12-12v-4c0-41.06 31.13-57.47 54.65-70.66 20.17-11.31 32.54-19 32.54-34 0-19.82-25.27-33-45.7-33-27.19 0-39.44 13.14-57.3 35.79a12 12 0 0 1-16.67 2.13L148.82 170a12 12 0 0 1-2.71-16.26C173.4 113 208.16 90 262.66 90c56.34 0 116.53 44 116.53 102 0 77-83.19 78.21-83.19 106.67z" opacity="0.4"></path><path class="fa-primary" fill="currentColor" d="M256 338a46 46 0 1 0 46 46 46 46 0 0 0-46-46zm6.66-248c-54.5 0-89.26 23-116.55 63.76a12 12 0 0 0 2.71 16.24l34.7 26.31a12 12 0 0 0 16.67-2.13c17.86-22.65 30.11-35.79 57.3-35.79 20.43 0 45.7 13.14 45.7 33 0 15-12.37 22.66-32.54 34C247.13 238.53 216 254.94 216 296v4a12 12 0 0 0 12 12h56a12 12 0 0 0 12-12v-1.33c0-28.46 83.19-29.67 83.19-106.67 0-58-60.19-102-116.53-102z"></path></g></svg>
      </div>

      <div id="show-settings">
        <svg  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512">
          <g>
            <path class="fa-secondary" fill="currentColor" d="M638.41 387a12.34 12.34 0 0 0-12.2-10.3h-16.5a86.33 86.33 0 0 0-15.9-27.4L602 335a12.42 12.42 0 0 0-2.8-15.7 110.5 110.5 0 0 0-32.1-18.6 12.36 12.36 0 0 0-15.1 5.4l-8.2 14.3a88.86 88.86 0 0 0-31.7 0l-8.2-14.3a12.36 12.36 0 0 0-15.1-5.4 111.83 111.83 0 0 0-32.1 18.6 12.3 12.3 0 0 0-2.8 15.7l8.2 14.3a86.33 86.33 0 0 0-15.9 27.4h-16.5a12.43 12.43 0 0 0-12.2 10.4 112.66 112.66 0 0 0 0 37.1 12.34 12.34 0 0 0 12.2 10.3h16.5a86.33 86.33 0 0 0 15.9 27.4l-8.2 14.3a12.42 12.42 0 0 0 2.8 15.7 110.5 110.5 0 0 0 32.1 18.6 12.36 12.36 0 0 0 15.1-5.4l8.2-14.3a88.86 88.86 0 0 0 31.7 0l8.2 14.3a12.36 12.36 0 0 0 15.1 5.4 111.83 111.83 0 0 0 32.1-18.6 12.3 12.3 0 0 0 2.8-15.7l-8.2-14.3a86.33 86.33 0 0 0 15.9-27.4h16.5a12.43 12.43 0 0 0 12.2-10.4 112.66 112.66 0 0 0 .01-37.1zm-136.8 44.9c-29.6-38.5 14.3-82.4 52.8-52.8 29.59 38.49-14.3 82.39-52.8 52.79zm136.8-343.8a12.34 12.34 0 0 0-12.2-10.3h-16.5a86.33 86.33 0 0 0-15.9-27.4l8.2-14.3a12.42 12.42 0 0 0-2.8-15.7 110.5 110.5 0 0 0-32.1-18.6A12.36 12.36 0 0 0 552 7.19l-8.2 14.3a88.86 88.86 0 0 0-31.7 0l-8.2-14.3a12.36 12.36 0 0 0-15.1-5.4 111.83 111.83 0 0 0-32.1 18.6 12.3 12.3 0 0 0-2.8 15.7l8.2 14.3a86.33 86.33 0 0 0-15.9 27.4h-16.5a12.43 12.43 0 0 0-12.2 10.4 112.66 112.66 0 0 0 0 37.1 12.34 12.34 0 0 0 12.2 10.3h16.5a86.33 86.33 0 0 0 15.9 27.4l-8.2 14.3a12.42 12.42 0 0 0 2.8 15.7 110.5 110.5 0 0 0 32.1 18.6 12.36 12.36 0 0 0 15.1-5.4l8.2-14.3a88.86 88.86 0 0 0 31.7 0l8.2 14.3a12.36 12.36 0 0 0 15.1 5.4 111.83 111.83 0 0 0 32.1-18.6 12.3 12.3 0 0 0 2.8-15.7l-8.2-14.3a86.33 86.33 0 0 0 15.9-27.4h16.5a12.43 12.43 0 0 0 12.2-10.4 112.66 112.66 0 0 0 .01-37.1zm-136.8 45c-29.6-38.5 14.3-82.5 52.8-52.8 29.59 38.49-14.3 82.39-52.8 52.79z" opacity="0.4"></path>
            <path class="fa-primary" fill="currentColor" d="M420 303.79L386.31 287a173.78 173.78 0 0 0 0-63.5l33.7-16.8c10.1-5.9 14-18.2 10-29.1-8.9-24.2-25.9-46.4-42.1-65.8a23.93 23.93 0 0 0-30.3-5.3l-29.1 16.8a173.66 173.66 0 0 0-54.9-31.7V58a24 24 0 0 0-20-23.6 228.06 228.06 0 0 0-76 .1A23.82 23.82 0 0 0 158 58v33.7a171.78 171.78 0 0 0-54.9 31.7L74 106.59a23.91 23.91 0 0 0-30.3 5.3c-16.2 19.4-33.3 41.6-42.2 65.8a23.84 23.84 0 0 0 10.5 29l33.3 16.9a173.24 173.24 0 0 0 0 63.4L12 303.79a24.13 24.13 0 0 0-10.5 29.1c8.9 24.1 26 46.3 42.2 65.7a23.93 23.93 0 0 0 30.3 5.3l29.1-16.7a173.66 173.66 0 0 0 54.9 31.7v33.6a24 24 0 0 0 20 23.6 224.88 224.88 0 0 0 75.9 0 23.93 23.93 0 0 0 19.7-23.6v-33.6a171.78 171.78 0 0 0 54.9-31.7l29.1 16.8a23.91 23.91 0 0 0 30.3-5.3c16.2-19.4 33.7-41.6 42.6-65.8a24 24 0 0 0-10.5-29.1zm-151.3 4.3c-77 59.2-164.9-28.7-105.7-105.7 77-59.2 164.91 28.7 105.71 105.7z"></path>
          </g>
        </svg>
      </div>
    </div>

    <div class='settingsControlsHidden' id="settings-controls-container"></div>
  </div>
</div>
`;
    },
  });
});
