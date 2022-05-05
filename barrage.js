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
      this._activeStates = ['placeEngineer', 'payResources', 'resolveChoice', 'confirmTurn', 'confirmPartialTurn'];
      this._notifications = [
        ['clearTurn', 1],
        ['refreshUI', 1],
        ['placeEngineers', null],
        ['payResources', null],
        ['gainResources', null],
        ['assignCompany', 1000],
        ['setupCompanies', 500],
        ['silentDestroy', null],
        ['moveDroplet', 1000],
      ];

      // Fix mobile viewport (remove CSS zoom)
      this.default_viewport = 'width=900';

      this._settingsConfig = {
        confirmMode: { type: 'pref', prefId: 103 },
        actionBoardBackground: {
          default: 0,
          name: _('Action Board Background'),
          attribute: 'action-background',
          type: 'select',
          values: {
            0: _('Image'),
            1: _('Plain'),
          },
        },
        actionBoardName: {
          default: 0,
          name: _('Action Board Names'),
          attribute: 'action-name',
          type: 'select',
          values: {
            0: _('Display'),
            1: _('Hide'),
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

      this.setupEnergyTrack();
      this.setupActionBoards();
      this.setupCompanies();
      this.setupMap();
      this.setupMeeples();

      this.inherited(arguments);
    },

    clearPossible() {
      this.inherited(arguments);
      dojo.query('.selected').removeClass('selected');
    },

    onEnteringState(stateName, args) {
      debug('Entering state: ' + stateName, args);
      if (this.isFastMode()) return;

      /*
      if (stateName == 'exchange' && args.args && args.args.automaticAction) {
        args.args.descSuffix = 'cook';
      }

      if (args.args && args.args.descSuffix) {
        this.changePageTitle(args.args.descSuffix);
      }

      if (args.args && args.args.optionalAction) {
        let base = args.args.descSuffix ? args.args.descSuffix : '';
        this.changePageTitle(base + 'skippable');
      }
      */

      if (this._activeStates.includes(stateName) && !this.isCurrentPlayerActive()) return;

      if (args.args && args.args.optionalAction && !args.args.automaticAction) {
        this.addSecondaryActionButton('btnPassAction', _('Pass'), () => this.takeAction('actPassOptionalAction'));
      }

      // Restart turn button
      if (
        args.args &&
        args.args.previousEngineChoices &&
        args.args.previousEngineChoices >= 1 &&
        !args.args.automaticAction
      ) {
        this.addDangerActionButton('btnRestartTurn', _('Restart turn'), () => {
          this.stopActionTimer();
          this.takeAction('actRestart');
        });
      }

      /*
  TODO
  if (this.isCurrentPlayerActive() && args.args) {
    // Anytime buttons
    if (args.args.anytimeActions) {
      args.args.anytimeActions.forEach((action, i) => {
        let msg = action.desc;
        msg = msg.log ? this.format_string_recursive(msg.log, msg.args) : _(msg);
        msg = this.formatStringMeeples(msg);

        this.addPrimaryActionButton(
          'btnAnytimeAction' + i,
          msg,
          () => this.takeAction('actAnytimeAction', { id: i }, false),
          'anytimeActions',
        );
      });
    }
  }
  */

      // Call appropriate method
      var methodName = 'onEnteringState' + stateName.charAt(0).toUpperCase() + stateName.slice(1);
      if (this[methodName] !== undefined) this[methodName](args.args);
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
      let oMap = dojo.place(`<div id='brg-map' data-map='${map.id}'></div>`, 'barrage-center-container');

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

      this.place('tplExit', '', oMap);
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

    tplExit() {
      return `<div class='hidden' id='exit'></div>`;
    },

    tplBasin(basin) {
      let cost = basin.cost > 0 ? 'paying' : '';
      return `<div class='basin' data-id='${basin.id}'></div>
        <div class='dam-slot ${cost}' data-id='${basin.id}'></div>`;
    },

    /////////////////////////////////////////////////////////////////////
    //  _____                              _____               _
    // | ____|_ __   ___ _ __ __ _ _   _  |_   _| __ __ _  ___| | __
    // |  _| | '_ \ / _ \ '__/ _` | | | |   | || '__/ _` |/ __| |/ /
    // | |___| | | |  __/ | | (_| | |_| |   | || | | (_| | (__|   <
    // |_____|_| |_|\___|_|  \__, |\__, |   |_||_|  \__,_|\___|_|\_\
    //                       |___/ |___/
    /////////////////////////////////////////////////////////////////////
    setupEnergyTrack() {
      let bonusTooltips = [
        _(
          'Score 2 Victory Points for each Contract you have fulfilled. Count all the Contract tiles (of any type) you have face down in your personal supply.',
        ),
        _('Score 4 Victory Points for each Base you have built.'),
        _('Score 4 Victory Points for each Elevation you have built.'),
        _('Score 4 Victory Points for each Conduit you have built.'),
        _('Score 5 Victory Points for each Powerhouse you have built.'),
        _(
          'Score 4 Victory Points for each Advanced Technology tile you have acquired. Count all the Advanced Technology tile in your personal supply and in your Construction Wheel. Basic Technology tiles do not count.',
        ),
        _('Score 5 Victory Points for each External Work you have fulfilled.'),
        _('Score 4 Victory Points for each Building you have built.'),
      ];

      // Place bonus tiles
      for (let i = 0; i < 6; i++) {
        let slot = dojo.place(`<div id='bonus-tile-slot-${i}' class='bonus-tile-slot'></div>`, 'energy-track');
        if (i == 0) {
          this.addCustomTooltip(slot, _('No bonus if < 6 energy'));
        } else {
          let bonusId = this.gamedatas.bonusTiles[i - 1];
          dojo.place(`<div class='bonus-tile' id='bonus-tile-${i}' data-id='${bonusId}'></div>`, slot);
          this.addCustomTooltip(`bonus-tile-${i}`, bonusTooltips[bonusId]);
        }
      }
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
      let container = dojo.place('<div id="action-boards-container"></div>', 'barrage-center-container');
      this.gamedatas.actionBoards.forEach((board) => {
        if (board.id == 'company') {
        } else {
          this.place('tplActionBoard', board, container);
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

      if (space.tooltip) {
        this.registerCustomTooltip(_(space.tooltip), space.uid);
      }

      return `<div id='${space.uid}' class='action-space ${space.cost > 0 ? 'paying' : ''}'>
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

    //////////////////////////////////////////////////////////////
    //  _____             _              _____ _
    // | ____|_ __   __ _(_)_ __   ___  |  ___| | _____      __
    // |  _| | '_ \ / _` | | '_ \ / _ \ | |_  | |/ _ \ \ /\ / /
    // | |___| | | | (_| | | | | |  __/ |  _| | | (_) \ V  V /
    // |_____|_| |_|\__, |_|_| |_|\___| |_|   |_|\___/ \_/\_/
    //              |___/
    //////////////////////////////////////////////////////////////
    onEnteringStateResolveChoice(args) {
      let addChoice = (choice, disabled) => {
        if ($('btnChoice' + choice.id)) return;

        let desc =
          typeof choice.description == 'string'
            ? _(choice.description)
            : this.format_string_recursive(_(choice.description.log), choice.description.args);

        this.addSecondaryActionButton(
          'btnChoice' + choice.id,
          desc,
          disabled ? () => {} : () => this.takeAction('actChooseAction', { id: choice.id }),
        );
        if (disabled) {
          dojo.addClass('btnChoice' + choice.id, 'disabled');
        }
      };

      Object.values(args.choices).forEach((choice) => addChoice(choice, false));
      Object.values(args.allChoices).forEach((choice) => addChoice(choice, true));
    },

    addConfirmTurn(args, action) {
      this.addPrimaryActionButton('btnConfirmTurn', _('Confirm'), () => {
        this.stopActionTimer();
        this.takeAction(action);
      });

      const OPTION_CONFIRM = 103;
      let n = args.previousEngineChoices;
      let timer = Math.min(10 + 2 * n, 20);
      this.startActionTimer('btnConfirmTurn', timer, this.prefs[OPTION_CONFIRM].value);
    },

    onEnteringStateConfirmTurn(args) {
      this.addConfirmTurn(args, 'actConfirmTurn');
    },

    onEnteringStateConfirmPartialTurn(args) {
      this.addConfirmTurn(args, 'actConfirmPartialTurn');
    },

    notif_clearTurn(n) {
      debug('Notif: restarting turn', n);
      this.cancelLogs(n.args.notifIds);
    },

    notif_refreshUI(n) {
      debug('Notif: refreshing UI', n);
      //      ['meeples', 'players', 'scores', 'playerCards'].forEach((value) => {
      ['meeples', 'players'].forEach((value) => {
        this.gamedatas[value] = n.args.datas[value];
      });
      this.setupMeeples();
      // this.updatePlayersScores();
    },

    ////////////////////////////////////////////////////////////////////////////////
    //     _   _                  _           _        _   _
    //    / \ | |_ ___  _ __ ___ (_) ___     / \   ___| |_(_) ___  _ __  ___
    //   / _ \| __/ _ \| '_ ` _ \| |/ __|   / _ \ / __| __| |/ _ \| '_ \/ __|
    //  / ___ \ || (_) | | | | | | | (__   / ___ \ (__| |_| | (_) | | | \__ \
    // /_/   \_\__\___/|_| |_| |_|_|\___| /_/   \_\___|\__|_|\___/|_| |_|___/
    //
    ////////////////////////////////////////////////////////////////////////////////

    // Generic call for Atomic Action that encode args as a JSON to be decoded by backend
    takeAtomicAction(action, args) {
      if (!this.checkAction(action)) return false;

      this.takeAction('actTakeAtomicAction', { actionArgs: JSON.stringify(args) }, false);
    },

    onEnteringStatePlaceEngineer(args) {
      Object.keys(args.spaces).forEach((uid) => {
        this.onClick(uid, () => {
          let choices = args.spaces[uid];
          if (choices.length == 1) {
            this.takeAtomicAction('actPlaceEngineer', [uid, choices[0]]);
          } else {
            this.clientState('placeEngineerChooseNumber', _('How many engineer do you want to place here ?'), {
              choices,
              uid,
            });
          }
        });
      });
    },

    onEnteringStatePlaceEngineerChooseNumber(args) {
      this.addCancelStateBtn();
      $(args.uid).classList.add('selected');
      args.choices.forEach((choice) => {
        this.addPrimaryActionButton('btnChoice' + choice, choice, () =>
          this.takeAtomicAction('actPlaceEngineer', [args.uid, choice]),
        );
      });
    },
  });
});
