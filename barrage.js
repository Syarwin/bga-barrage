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
      this._activeStates = [
        'placeEngineer',
        'payResources',
        'placeDroplet',
        'construct',
        'placeStructure',
        'produce',
        'takeContract',
        'fulfillContract',
        'resolveChoice',
        'confirmTurn',
        'confirmPartialTurn',
      ];
      this._notifications = [
        ['clearTurn', 1],
        ['startNewRound', 1],
        ['refreshUI', 1],
        ['placeEngineers', null],
        ['payResources', null],
        ['payResourcesToWheel', null],
        ['gainResources', null],
        ['collectResources', null],
        ['recoverResources', null],
        ['assignCompany', 1000],
        ['setupCompanies', 500],
        ['silentDestroy', 100],
        ['moveDroplets', null],
        ['produce', 1500],
        ['incEnergy', 800],
        ['resetEnergies', null],
        ['score', 500],
        ['rotateWheel', 1000],
        ['construct', null],
        ['updateIncome', 200],
        ['pickContracts', 1000],
        ['fulfillContract', 2000],
        ['refillStacks', 1000],
        ['updateTurnOrder', 500],
        ['flipToken', 500],
        ['refillTechTiles', 1000],
      ];

      // Fix mobile viewport (remove CSS zoom)
      this.default_viewport = 'width=1328';

      this._antonTile = 0;

      this._settingsConfig = {
        confirmMode: { type: 'pref', prefId: 103 },
        mapScale: {
          default: 60,
          name: _('Map scale'),
          type: 'slider',
          sliderConfig: {
            step: 5,
            padding: 10,
            range: {
              min: [30],
              max: [100],
            },
          },
        },
        map: {
          default: 1,
          name: _('Enhanced map display'),
          attribute: 'map',
          type: 'switch',
        },
        conduits: {
          default: 1,
          name: _('Conduit values'),
          attribute: 'conduit',
          type: 'switch',
        },
        background: {
          default: 0,
          name: _('Background'),
          attribute: 'background',
          type: 'select',
          values: {
            0: _('Dark Barrage texture'),
            1: _('Light Barrage texture'),
            2: _('Default BGA'),
          },
        },
        actionBoardBackground: {
          default: 0,
          name: _('Action Board Background'),
          attribute: 'action-background',
          type: 'select',
          values: {
            0: _('Image'),
            1: _('Plain with distinct color'),
            2: _('Plain with same color'),
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
            2: _('Hide and collapse borders'),
            3: _('Hide and blend boards with background'),
          },
        },
        energyTrack: {
          default: 0,
          name: _('Energy track position'),
          attribute: 'energy-track',
          type: 'select',
          values: {
            0: _('Above the map'),
            1: _('Right of the map'),
          },
        },
        wheelSummaries: {
          default: 0,
          name: _('Wheel summaries in player panels'),
          attribute: 'wheel-summary',
          type: 'select',
          values: {
            0: _('Displayed for all players'),
            1: _('Only opponents'),
            2: _('Only mine'),
            3: _('None'),
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
      // dojo.destroy('debug_output');

      this.setupEnergyTrack();
      this.setupCompanies();
      this.setupActionBoards();
      this.setupMap();
      this.setupMeeples();
      this.setupContracts();
      this.setupTechnologyTiles();
      this.updateWheelSummaries();

      // Create a new div for "anytime" buttons
      dojo.place(
        "<div id='anytimeActions' style='display:inline-block;float:right'></div>",
        $('generalactions'),
        'after',
      );
      // Create the "go to top" button
      dojo.place("<div id='go-to-top'></div>", $('active_player_statusbar'), 'before');
      $('go-to-top').addEventListener('click', () => window.scrollTo(0, 0));

      this.inherited(arguments);
    },

    clearPossible() {
      this.inherited(arguments);
      dojo.empty('anytimeActions');

      dojo.query('.selected').removeClass('selected');
      dojo.query('.headstream[data-n]').forEach((h) => (h.dataset.n = 0));
    },

    onEnteringState(stateName, args) {
      debug('Entering state: ' + stateName, args);
      if (this.isFastMode()) return;

      if (args.args && args.args.descSuffix) {
        this.changePageTitle(args.args.descSuffix);
      }

      if (args.args && args.args.optionalAction) {
        let base = args.args.descSuffix ? args.args.descSuffix : '';
        this.changePageTitle(base + 'skippable');
      }

      if (this._activeStates.includes(stateName) && !this.isCurrentPlayerActive()) return;

      if (args.args && args.args.optionalAction && !args.args.automaticAction) {
        this.addSecondaryActionButton('btnPassAction', _('Pass'), () => this.takeAction('actPassOptionalAction'));
      }

      // Restart turn button
      if (
        args.args &&
        args.args.previousEngineChoices &&
        args.args.previousEngineChoices >= 1 &&
        !args.args.automaticAction &&
        this.isCurrentPlayerActive()
      ) {
        this.addDangerActionButton(
          'btnRestartTurn',
          _('Restart turn'),
          () => {
            this.stopActionTimer();
            this.takeAction('actRestart');
          },
          'restartAction',
        );
      }

      if (this.isCurrentPlayerActive() && args.args) {
        // Alternative actions buttons
        if (args.args.alternativeActions) {
          args.args.alternativeActions.forEach((action, i) => {
            let msg = action.desc;
            msg = msg.log ? this.format_string_recursive(msg.log, msg.args) : _(msg);
            msg = this.formatString(msg);

            this.addPrimaryActionButton(
              'btnAlternativeAction' + i,
              msg,
              () => this.takeAction('actAlternativeAction', { id: i }, false),
              action.resolve === false ? 'anytimeActions' : 'customActions',
            );
          });
        }
      }

      // Call appropriate method
      var methodName = 'onEnteringState' + stateName.charAt(0).toUpperCase() + stateName.slice(1);
      if (this[methodName] !== undefined) this[methodName](args.args);
    },

    onEnteringStateSpecialEffect(args) {
      if (args.description) {
        this.gamedatas.gamestate.descriptionmyturn = args.description;
        this.gamedatas.gamestate.descriptionmyturn = args.description;

        // this.gamedatas.gamestate.description = this.format_string_recursive(args.description, {
        //   actplayer: this.gamedatas.players[this.getActivePlayerId()].name,
        // });
        this.updatePageTitle();
      }
      if (!this.isCurrentPlayerActive()) {
        // dojo.destroy('btnPassAction');
        dojo.destroy('btnRestartTurn');
        return;
      }

      let method = args.method;
      if (this[method] != undefined) {
        this[method](args);
      }
    },

    copyPower(args) {
      Object.keys(args.power).forEach((key) => {
        this.addPrimaryActionButton('btnPower' + args.power[key].id, _('Use ') + args.power[key].officer.name, () => {
          this.takeAtomicAction('actCopyPower', [args.power[key].id]);
        });
      });
    },

    notif_startNewRound(n) {
      debug('Notif: starting new round', n);
      this.gamedatas.bonuses = n.args.bonuses;
      this.updateCompanyBonuses();
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
      dojo.place(
        `<div id='brg-map-resizable' class="barrage-frame" data-map='${map.id}'><div id='brg-map'></div></div>`,
        'map-energy-wrapper',
      );
      let oMap = $('brg-map');

      let svgIds = {
        1: 'base-map-svg',
      };
      dojo.place($(svgIds[map.id]), oMap);

      // Headstreams
      Object.keys(map.headstreams).forEach((hId) =>
        this.place('tplHeadstream', { hId, tileId: map.headstreams[hId] }, oMap),
      );

      // Conduits
      Object.keys(map.conduits).forEach((cId) => {
        let conduit = map.conduits[cId];
        conduit.cId = cId;
        let o = this.place('tplConduitSlot', conduit, oMap);
        o.addEventListener('mouseenter', () => {
          dojo.query(`.powerhouse-slot[data-zone="${map.conduits[cId].end}"]`).addClass('highlight');
        });
        o.addEventListener('mouseleave', () => {
          dojo.query('.powerhouse-slot.highlight').removeClass('highlight');
        });
      });

      let powerhouseZones = [];
      // Powerhouses
      map.powerhouses.forEach((powerhouse) => {
        this.place('tplPowerhouseSlot', powerhouse, oMap);
        powerhouseZones.push(powerhouse.zone);
      });

      // Basins
      let basinZones = [];
      map.basins.forEach((basin) => {
        this.place('tplBasin', basin, oMap);
        basinZones.push(basin.zone);
      });

      // Zone overlays
      map.zoneIds.forEach((zoneId) => {
        if (powerhouseZones.includes(zoneId)) {
          this.place('tplPowerhouseZone', { id: zoneId }, oMap);
        }

        if (basinZones.includes(zoneId)) {
          this.place('tplBasinZone', { id: zoneId }, oMap);
        }
      });

      this.place('tplExit', '', oMap);
    },

    getConstructSlot(uid) {
      return $('brg-map').querySelector(`:not(.basin)[data-id='${uid}']`);
    },

    tplHeadstream(headstream) {
      return `<div class='headstream' data-id='${headstream.hId}'>
        <div class='headstream-tile' data-tile='${headstream.tileId}'></div>
      </div>`;
    },

    tplConduitSlot(conduit) {
      return `<div class='conduit-slot' data-id='${conduit.cId}' data-production='${conduit.production}'></div>`;
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

    tplPowerhouseZone(zone) {
      return `<div class='powerhouse-zone' data-zone="${zone.id}"></div>`;
    },

    tplBasinZone(zone) {
      return `<div class='basin-zone' data-zone="${zone.id}"></div>`;
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

      let objectiveTooltips = [
        _('Count all the Bases and all the Powherouses you have built in building spaces with a red bordered icons.'),
        _(
          'Count all the structure pieces (of any type) in the area of the Map (Mountains, Hills or Plains) where you have built the most structure pieces.',
        ),
        _(
          'Count all your Bases connected by a Conduit of your color to a Powerhouse of your color. If there are two Bases connected to the same Powerhouse they both count.',
        ),
        _(
          'Count all the structure pieces (of any type) in the area of the Map (Mountains, Hills or Plains) where you have built the least structure pieces.',
        ),
        _(
          'Count all the basins where you have built at least one structure piece of any type. The maxium is 12 (one structure in all the twelve basins).',
        ),
        _(
          'Count all the basins where you have built at least three structure piece of any type. The maxium is 5 (three structures in five basins).',
        ),
      ];
      let objectiveTooltipsGeneric = _(
        "Determine the players' classification according to that condition. The first player scores 15 Victory Points, the second player scores 10 VPs and the third player scores 5VPs. In case of a tie, evenly divide the VPs of the respective tiers among the players who tied (round up if necessary)",
      );

      // Place bonus tiles
      for (let i = 0; i < 6; i++) {
        let portion = dojo.place('<div class="energy-track-portion"></div', 'energy-track-board');
        if (i == 0) {
          // First/second bonus
          let bonus = dojo.place('<div id="energy-track-first-second-bonus"><div></div><div></div></div>', portion);
          this.addCustomTooltip(
            bonus,
            _('The first player on the Energy Track scores 6 Victory Points; the second scores 2 Victory Points.'),
          );
        }

        let slot = dojo.place(`<div id='bonus-tile-slot-${i}' class='bonus-tile-slot'></div>`, portion);
        if (i == 0) {
          this.addCustomTooltip(slot, _('No bonus if < 6 energy'));
        } else if (i < this.gamedatas.round) {
          // do nothing
        } else {
          let bonusId = this.gamedatas.bonusTiles[i - 1];
          dojo.place(`<div class='bonus-tile' id='bonus-tile-${i}' data-id='${bonusId}'></div>`, slot);
          this.addCustomTooltip(`bonus-tile-${i}`, bonusTooltips[bonusId]);
        }
      }

      // Place objective tile
      let objective = this.gamedatas.objectiveTile;
      let portion = dojo.place('<div class="energy-track-portion"></div', 'energy-track-board');
      dojo.place(`<div class='objective-tile' id='objective-tile' data-id='${objective}'></div>`, portion);
      this.addCustomTooltip('objective-tile', objectiveTooltips[objective - 1] + objectiveTooltipsGeneric);

      // Place track
      let track = dojo.place('<div id="energy-track"></div>', 'energy-track-board');
      let bonuses = { 29: 8, 22: 7, 16: 6, 11: 5, 7: 4, 4: 3, 2: 2, 1: 1, 0: 3 };
      for (let i = 0; i < 32; i++) {
        let bonus = bonuses[i] ? ` data-bonus='${bonuses[i]}'` : '';
        if (i == 0) {
          let slot = dojo.place(
            `<div id='energy-track-${i}' class='energy-track-slot' data-i='${i}' ${bonus}></div>`,
            track,
          );
        } else {
          let slot = dojo.place(
            `<div id='energy-track-${i}' class='energy-track-slot' data-i='${i}' ${bonus}></div>`,
            track,
          );
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
      this.gamedatas.actionBoards.forEach((board) => {
        if (board.id == 'company') {
          board.structure.forEach((row) => {
            let cId = row[0].cId;
            let container = '';
            // Mahiri management to add the action space
            if (row[1].hasOwnProperty('type') != -1 && row[1]['type'] == 'mahiri') {
              if (
                document.querySelector(`.company-board[data-company='${cId}'] .officer-symbol .action-board-row`) ===
                null
              ) {
                container = document.querySelector(`.company-board[data-company='${cId}'] .officer-symbol`);
                this.place('tplActionBoardRow', row, container);
              } else {
                container = document.querySelector(
                  `.company-board[data-company='${cId}'] .officer-symbol .action-board-row`,
                );
                this.place('tplActionSpace', row[0], container);
              }
            } else {
              container = document.querySelector(`.action-board[data-id='company-${cId}'] .action-board-inner`);
              this.place('tplActionBoardRow', row, container);
            }
          });
        } else {
          this.place('tplActionBoard', board, 'action-boards-container');
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
      if (typeof row === 'string' || row instanceof String) {
        let content = '';
        if (row == 'private-contracts') {
          content = `
            <div class="contract-stack" id="contract-stack-2"><div class="contract-counter" id="contract-counter-2">10</div></div>
            <div class="contract-stack" id="contract-stack-3"><div class="contract-counter" id="contract-counter-3">11</div></div>
            <div class="contract-stack" id="contract-stack-4"><div class="contract-counter" id="contract-counter-4">12</div></div>`;
        }

        return `<div id='${row}'>${content}</div>`;
      }

      let slots = row.map((slot) => {
        if (slot['i'] != undefined) {
          let id = this.registerCustomTooltip(_(slot.t));
          return `<div id="${id}" class="action-board-icon">${this.formatString(slot.i)}</div>`;
        } else if (slot['type'] != undefined) {
          return '';
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

      this._settingsModal = new customgame.modal('showSettings', {
        class: 'barrage_popin',
        closeIcon: 'fa-times',
        title: _('Settings'),
        closeAction: 'hide',
        verticalAlign: 'flex-start',
        contentsTpl: `<div id="settings-controls-container"></div>`,
      });
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
  </div>
</div>
`;
    },

    onChangeMapScaleSetting(scale) {
      let elt = document.documentElement;
      elt.style.setProperty('--barrageMapScale', scale / 100);
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
      ['meeples', 'players', 'companies', 'techTiles', 'contracts', 'bonuses'].forEach((value) => {
        this.gamedatas[value] = n.args.datas[value];
      });
      this.setupMeeples();
      this.setupTechnologyTiles();
      this.setupContracts();
      this.refreshCompanies();
      this.updateWheelSummaries();
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
      debug('TakeAtomicAction ', args);
      this.takeAction('actTakeAtomicAction', { actionArgs: JSON.stringify(args) }, false);
    },

    // Place engineer
    onEnteringStatePlaceEngineer(args) {
      let construct = null;
      Object.keys(args.spaces).forEach((uid) => {
        if (args.constructSpaces.includes(uid)) {
          if (construct === null || args.spaces[construct][0] > args.spaces[uid][0]) {
            construct = uid;
          }
        }
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

      if (construct !== null) {
        let n = args.spaces[construct][0];
        this.addPrimaryActionButton(
          'btnConstruct',
          this.translate({
            log: _('Construct with ${n} engineer(s)'),
            args: { n },
          }),
          () => this.takeAtomicAction('actPlaceEngineer', [construct, n]),
        );
      }

      if (args.canSkip) {
        this.addDangerActionButton('btnSkip', _('Skip turn'), () => {
          this.takeAction('actSkip');
        });
      }
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

    // Place Droplets
    onEnteringStatePlaceDroplet(args) {
      let headstreams = args.headstreams.map((hId) => $('brg-map').querySelector(`.headstream[data-id='${hId}']`));
      let currentSelection = [];
      let updateSelectable = () => {
        headstreams.forEach((h) => {
          h.classList.toggle('selectable', currentSelection.length < args.n);
          h.dataset.n = currentSelection.reduce((c, v) => c + (v == h.dataset.id), 0);
        });

        dojo.destroy('btnConfirmDroplets');
        dojo.destroy('btnCancelDroplets');
        if (currentSelection.length > 0) {
          this.addSecondaryActionButton('btnCancelDroplets', _('Cancel'), () => {
            currentSelection = [];
            updateSelectable();
          });
          this.addPrimaryActionButton('btnConfirmDroplets', _('Confirm'), () => {
            this.takeAtomicAction('actPlaceDroplet', [currentSelection]);
          });
        }
      };

      args.headstreams.forEach((hId) => {
        let headstream = $('brg-map').querySelector(`.headstream[data-id='${hId}']`);
        this.onClick(headstream, () => {
          if (currentSelection.length < args.n) {
            currentSelection.push(hId);
            updateSelectable();
          }
        });
      });
    },

    // Place structure
    onEnteringStatePlaceStructure(args) {
      if (args.descSuffix == 'auto') return;

      args.spaces.forEach((uid) => {
        let elt = this.getConstructSlot(uid);
        this.onClick(elt, () => this.takeAtomicAction('actPlaceStructure', [uid]));
      });
    },

    // Take contract
    onEnteringStateTakeContract(args) {
      let contracts = {};
      args.contractIds.forEach((cId) => (contracts[cId] = $(`contract-${cId}`)));

      this.onSelectN(contracts, args.n, (cIds) => this.takeAtomicAction('actTakeContract', [cIds]));
    },

    // Take contract
    onEnteringStateDiscardContract(args) {
      let contracts = {};
      args.contracts.forEach((cId) => (contracts[cId] = $(`contract-${cId}`)));

      this.onSelectN(contracts, args.n, (cIds) => this.takeAtomicAction('actDiscardContract', [cIds]));
    },

    // Construct
    onEnteringStateConstruct(args) {
      // Compute for each tile, the corresponding spaces, and vice-versa
      let byTile = [];
      let bySpace = args.spaces;
      let tileIds = [];
      let spaceIds = [];
      Object.keys(args.spaces).forEach((spaceId) => {
        spaceIds.push(spaceId);
        args.spaces[spaceId].forEach((tileId) => {
          if (!byTile[tileId]) {
            byTile[tileId] = [];
            tileIds.push(tileId);
          }
          byTile[tileId].push(spaceId);
        });
      });

      // Store the selected tile and space
      let selectedTile = null;
      let selectedSpace = null;
      let copiedTile = null;
      let updateStatus = () => {
        tileIds.forEach((tileId) => {
          let elt = $(`tech-tile-${tileId}`);
          elt.classList.toggle(
            'selectable',
            (tileId == selectedTile || selectedTile == null) &&
              (selectedSpace == null || bySpace[selectedSpace].includes(tileId)),
          );
          elt.classList.toggle('selected', tileId == selectedTile);
        });
        spaceIds.forEach((spaceId) => {
          let elt = this.getConstructSlot(spaceId);
          elt.classList.toggle(
            'selectable',
            (spaceId == selectedSpace || selectedSpace == null) &&
              (selectedTile == null || byTile[selectedTile].includes(spaceId)),
          );
          elt.classList.toggle('selected', spaceId == selectedSpace);
        });

        args.antonPower.forEach((t) => {
          let elt = $(`tech-tile-${t.id}`);
          elt.classList.toggle('selectable', false);
        });

        dojo.destroy('btnConfirmConstruct');
        dojo.destroy('btnCancelConstruct');
        if (selectedTile != null || selectedSpace != null) {
          this.addSecondaryActionButton('btnCancelConstruct', _('Cancel'), () => {
            selectedTile = null;
            selectedSpace = null;
            copiedTile = null;
            updateStatus();
          });
        }

        if (selectedTile == this._antonTile && selectedSpace != null && copiedTile == null) {
          debug('Anton tile copied tile todo');
          this.gamedatas.gamestate.descriptionmyturn = _('You must select a Technology tile to copy');
          this.updatePageTitle();

          args.antonPower.forEach((aTile) => {
            let elt = $(`tech-tile-${aTile.id}`);
            elt.classList.toggle('selectable', true);
            this.onClick(elt, () => {
              if (!elt.classList.contains('selectable')) return;

              if (copiedTile == aTile.id) {
                copiedTile = null;
              } else {
                copiedTile = aTile.id;
              }
              elt.classList.toggle('selected', aTile.id == copiedTile);

              updateStatus();
            });
          });
        } else if (selectedTile != null && selectedSpace != null) {
          this.addPrimaryActionButton('btnConfirmConstruct', _('Confirm'), () =>
            this.takeAtomicAction('actConstruct', [selectedSpace, selectedTile, copiedTile]),
          );
        }
      };

      // Add listeners
      tileIds.forEach((tileId) => {
        let elt = $(`tech-tile-${tileId}`);
        this.onClick(elt, () => {
          if (!elt.classList.contains('selectable')) return;

          if (selectedTile == tileId) {
            selectedTile = null;
          } else {
            selectedTile = tileId;
            if (byTile[tileId].length == 1) {
              selectedSpace = byTile[tileId][0];
            }
          }
          updateStatus();
        });
      });
      spaceIds.forEach((spaceId) => {
        let elt = this.getConstructSlot(spaceId);
        this.onClick(elt, () => {
          if (!elt.classList.contains('selectable')) return;

          if (selectedSpace == spaceId) {
            selectedSpace = null;
          } else {
            selectedSpace = spaceId;
            if (bySpace[spaceId].length == 1) {
              selectedTile = bySpace[spaceId][0];
            }
          }
          updateStatus();
        });
      });
    },

    // Produce
    onEnteringStateProduce(args) {
      // Compute aggregated data
      let systems = args.systems;
      let byPowerhouse = [];
      let byBasin = [];
      let byConduit = [];
      systems.forEach((system, i) => {
        system.id = i;

        if (!byPowerhouse[system.powerhouseSpaceId]) byPowerhouse[system.powerhouseSpaceId] = [];
        byPowerhouse[system.powerhouseSpaceId].push(i);

        if (!byBasin[system.basin]) byBasin[system.basin] = [];
        byBasin[system.basin].push(i);

        if (!byConduit[system.conduitSpaceId]) byConduit[system.conduitSpaceId] = [];
        byConduit[system.conduitSpaceId].push(i);
      });

      // Store the selected stuff
      let selectedConduit = null;
      let selectedPowerhouse = null;
      let selectedBasin = null;
      let optionalAction = args && args.optionalAction && !args.automaticAction;
      debug('optional', optionalAction);
      let updateStatus = () => {
        dojo.query('#brg-map .selectable').removeClass('selectable selected');
        // Keep only available systems
        let possibleSystems = systems.filter(
          (system, i) =>
            (selectedConduit == null || byConduit[selectedConduit].includes(i)) &&
            (selectedPowerhouse == null || byPowerhouse[selectedPowerhouse].includes(i)) &&
            (selectedBasin == null || byBasin[selectedBasin].includes(i)),
        );
        possibleSystems.forEach((system) => {
          let basin = this.getConstructSlot(system.basin);
          basin.classList.toggle('selectable', systems.length > 1);
          basin.classList.toggle('selected', system.basin == selectedBasin);

          let powerhouse = this.getConstructSlot(system.powerhouseSpaceId);
          powerhouse.classList.toggle('selectable', systems.length > 1);
          powerhouse.classList.toggle('selected', system.powerhouseSpaceId == selectedPowerhouse);

          let conduit = this.getConstructSlot(system.conduitSpaceId);
          conduit.classList.toggle('selectable', systems.length > 1);
          conduit.classList.toggle('selected', system.conduitSpaceId == selectedConduit);
        });

        dojo.empty('customActions');

        if (selectedBasin != null || selectedPowerhouse != null || selectedConduit != null) {
          if (systems.length > 1) {
            this.addSecondaryActionButton('btnCancelProduce', _('Cancel'), () => {
              selectedBasin = null;
              selectedPowerhouse = null;
              selectedConduit = null;
              updateStatus();
            });
          }
        }
        if (selectedBasin != null && selectedPowerhouse != null && selectedConduit != null) {
          let system = possibleSystems[0];
          Object.keys(system.productions).forEach((nDroplets) => {
            let production = system.productions[nDroplets];
            let msg = this.formatString(
              dojo.string.substitute(_('Produce <ENERGY:${n}> with ${m} <WATER>'), { n: production, m: nDroplets }),
            );
            this.addPrimaryActionButton('btnConfirmProduce' + nDroplets, msg, () =>
              this.takeAtomicAction('actProduce', [system.id, nDroplets]),
            );
          });
        } else if (possibleSystems.length == 1) {
          let system = possibleSystems[0];
          selectedBasin = system.basin;
          selectedPowerhouse = system.powerhouseSpaceId;
          selectedConduit = system.conduitSpaceId;
          updateStatus();
        }
        if (optionalAction) {
          this.addSecondaryActionButton('btnPassAction', _('Pass'), () => this.takeAction('actPassOptionalAction'));
        }
      };

      // Add listeners
      systems.forEach((system) => {
        let basin = this.getConstructSlot(system.basin);
        this.onClick(basin, () => {
          selectedBasin = selectedBasin == system.basin ? null : system.basin;
          updateStatus();
        });

        let powerhouse = this.getConstructSlot(system.powerhouseSpaceId);
        this.onClick(powerhouse, () => {
          selectedPowerhouse = selectedPowerhouse == system.powerhouseSpaceId ? null : system.powerhouseSpaceId;
          updateStatus();
        });

        let conduit = this.getConstructSlot(system.conduitSpaceId);
        this.onClick(conduit, () => {
          selectedConduit = selectedConduit == system.conduitSpaceId ? null : system.conduitSpaceId;
          updateStatus();
        });
      });

      // Autoselect if only one
      updateStatus();
    },

    // Fulfill contract
    onEnteringStateFulfillContract(args) {
      let contracts = {};
      args.contractIds.forEach((cId) => (contracts[cId] = $(`contract-${cId}`)));

      this.onSelectN(contracts, 1, (cIds) => this.takeAtomicAction('actFulfillContract', [cIds[0]]));
    },

    //////////////////////////////////////////////////////
    //   ____            _                  _
    //  / ___|___  _ __ | |_ _ __ __ _  ___| |_ ___
    // | |   / _ \| '_ \| __| '__/ _` |/ __| __/ __|
    // | |__| (_) | | | | |_| | | (_| | (__| |_\__ \
    //  \____\___/|_| |_|\__|_|  \__,_|\___|\__|___/
    //
    //////////////////////////////////////////////////////
    setupContracts() {
      // This function is refreshUI compatible
      let contractIds = this.gamedatas.contracts.board.map((contract) => {
        this.addContract(contract);
        let o = $(`contract-${contract.id}`);
        let container = this.getContractContainer(contract);
        if (o.parentNode != $(container)) {
          dojo.place(o, container);
        }

        return contract.id;
      });

      if (!this._contractStackCounters) {
        this._contractStackCounters = {};
        for (let i = 2; i <= 4; i++) {
          this._contractStackCounters[i] = this.createCounter(
            `contract-counter-${i}`,
            this.gamedatas.contracts.stacks[i],
          );
        }
      } else {
        for (let i = 2; i <= 4; i++) {
          this._contractStackCounters[i].setValue(this.gamedatas.contracts.stacks[i]);
        }
      }

      this.updateCompaniesCounters();
    },

    addContract(contract, container = null) {
      if ($('contract-' + contract.id)) return;

      if (!container) {
        container = this.getContractContainer(contract);
      }
      this.place('tplContract', contract, container);
      this.addCustomTooltip(`contract-${contract.id}`, this.tplContractTooltip(contract));
    },

    getContractContainer(contract) {
      if (contract.location == 'pickStart') {
        return 'pickStart-contracts';
      } else if (contract.location.substr(0, 4) == 'hand') {
        let cId = contract.location.substr(5);
        return `company-contracts-${cId}`;
      } else if (contract.location.substr(0, 9) == 'fulfilled') {
        let cId = contract.location.substr(10);
        return `reserve_${cId}_fcontract`;
      } else if ($(contract.location)) {
        return $(contract.location);
      }

      console.error('Trying to get container of a contract', contract);
      return 'game_play_area';
    },

    tplContract(contract, tooltip = false) {
      let icons = this.convertFlowToIcons(contract.reward);
      contract.parity = contract.parity === undefined ? contract.id % 2 : contract.parity;
      return (
        `<div id='contract-${contract.id}${tooltip ? '-tooltip' : ''}' class='barrage-contract ${
          contract.id == -1 ? 'fake' : ''
        }' data-parity='${contract.parity}'>
          <div class='contract-fixed-size'>
            <div class='energy-cost'>${contract.cost}</div>
            <div class='contract-reward' data-type='${contract.type}'>
              <div class='contract-reward-row'>${icons.length > 0 ? icons[0] : ''}</div>` +
        (icons.length > 1 ? `<div class='contract-reward-row'>${icons.slice(1).join('')}</div>` : '') +
        `
            </div>
          </div>
      </div>`
      );
    },

    tplContractTooltip(contract) {
      let descs = this.convertFlowToDescs(contract.reward);
      return (
        this.tplContract(contract, true) +
        `
      <div class='contract-desc'>
        ` +
        descs.join('<br />') +
        `
      </div>`
      );
    },

    notif_pickContracts(n) {
      debug('Notif: someone picked contract(s)', n);
      n.args.contracts.forEach((contract) => {
        $(`contract-${contract.id}`).classList.remove('selected');
        this.slide(`contract-${contract.id}`, this.getContractContainer(contract));
      });
    },

    notif_refillStacks(n) {
      debug('Notif: refilling contract stack', n);
      n.args.contracts.forEach((contract) => {
        let type = contract.type;
        this.addContract(contract, `contract-counter-${type}`);
        this._contractStackCounters[type].incValue(-1);
        this.slide(`contract-${contract.id}`, this.getContractContainer(contract));
      });
    },

    notif_fulfillContract(n) {
      debug('Notif: someone fulfilled a contract', n);
      let contract = n.args.contract;
      let fakeContract = {
        id: -1,
        type: contract.type,
        location: contract.location,
        cost: '',
        icons: [],
        parity: 1 - (contract.id % 2),
      };

      this.gamedatas.bonuses = n.args.bonuses;
      this.updateCompanyBonuses();

      if (this.isFastMode()) {
        dojo.place(`contract-${id}`, this.getContractContainer(contract));
        return;
      }

      this.flipAndReplace(`contract-${contract.id}`, this.tplContract(fakeContract)).then(() => {
        if (contract.type == 1) {
          this.slide('contract--1', this.getContractContainer(fakeContract), {
            destroy: true,
          }).then(() => this.addContract(contract));
        } else {
          let o = $('contract--1');
          o.style.transform = 'translateX(0px)';
          o.style.transition = 'transform 0.8s';
          let dx = -o.offsetLeft - o.offsetWidth - 20;
          o.style.transform = `translateX(${dx}px)`;
          this.wait(800).then(() => {
            o.remove();
            this.addContract(contract);
            this.updateResourcesHolders(false, false);
          });
        }
      });
    },

    onEnteringStatePayResources(args) {
      if (args.combinations.length == 1) {
        return;
      }

      args.combinations.forEach((cost, i) => {
        // Compute desc
        let log = '',
          arg = {};
        if (cost.card == undefined) {
          if (cost.sources && cost.sources.length) {
            log = _('Pay ${resource} (${cards})');
            arg.resource = this.formatResourceArray(cost);
            arg.cards = cost.sources.map((cardId) => _(args.cardNames[cardId])).join(', ');
          } else {
            log = _('Pay ${resource}');
            arg.resource = this.formatResourceArray(cost);
          }
        } else {
          log = _('Return ${card}');
          arg.card = _(args.cardNames[cost.card]);
        }
        let desc = this.format_string_recursive(log, arg);

        // Add button
        this.addSecondaryActionButton('btnChoicePay' + i, desc, () => this.takeAtomicAction('actPay', [cost]));
      });
    },

    ////////////////////////////////////////////////////////
    //  _____         _       _____ _ _
    // |_   _|__  ___| |__   |_   _(_) | ___  ___
    //   | |/ _ \/ __| '_ \    | | | | |/ _ \/ __|
    //   | |  __/ (__| | | |   | | | | |  __/\__ \
    //   |_|\___|\___|_| |_|   |_| |_|_|\___||___/
    //
    ////////////////////////////////////////////////////////
    setupTechnologyTiles() {
      // This function is refreshUI compatible
      let tilesIds = this.gamedatas.techTiles.map((tile) => {
        this.addTechTile(tile);
        let o = $(`tech-tile-${tile.id}`);
        let container = this.getTechTileContainer(tile);
        if (o.parentNode != $(container)) {
          dojo.place(o, container);
        }
        if (tile.type == 'anton') {
          this._antonTile = tile.id;
        }
        return tile.id;
      });
    },

    addTechTile(tile, container = null) {
      if ($(`tech-tile-${tile.id}`)) return;

      if (!container) {
        container = this.getTechTileContainer(tile);
      }
      this.place('tplTechTile', tile, container);
      this.addCustomTooltip(`tech-tile-${tile.id}`, this.tplTechTileTooltip(tile));
    },

    getTechTileContainer(tile) {
      if (tile.location == 'company') {
        return `company-tech-tiles-${tile.cId}`;
      }
      // Resource wheel
      else if (tile.location == 'wheel') {
        let n = 1 + parseInt(tile.state);
        return $(`wheel-${tile.cId}`).querySelector(`.wheel-sector:nth-of-type(${n}) .wheel-tile-slot`);
      } else if ($(tile.location)) {
        return $(tile.location);
      }

      console.error('Trying to get container of a tech tile', tile);
      return 'game_play_area';
    },

    tplTechTile(tile, tooltip = false) {
      let t = tooltip ? '-tooltip' : '';
      return `<div id='tech-tile-${tile.id}${t}' class='barrage-tech-tile'>
          <div class='tech-tile-fixed-size' data-type='${tile.type}'>
            <div class='tech-tile-image'></div>
          </div>
      </div>`;
    },

    tplTechTileTooltip(tile) {
      return (
        this.tplTechTile(tile, true) +
        `
      <div class='tile-desc'>
        ` +
        tile.descs.map((t) => this.translate(t)).join('<br />') +
        `
      </div>`
      );
    },

    notif_refillTechTiles(n) {
      debug('Notif: refilling advanced tiles stack', n);
      n.args.tiles.forEach((tile) => {
        this.addTechTile(tile);
        // let o = $(`tech-tile-${tile.id}`);
        // let container = this.getTechTileContainer(tile);
        this.slide(`tech-tile-${tile.id}`, this.getTechTileContainer(tile));

        // return tile.id;
      });
    },
  });
});
