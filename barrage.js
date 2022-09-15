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
        ['changePhase', 1],
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
        ['mahiriCopy', 100],
        ['clearMahiri', 10],
        ['flipAutomaCard', 800],
        ['emptyContractStack', 800],
      ];

      // Fix mobile viewport (remove CSS zoom)
      this.default_viewport = 'width=1100';

      this._antonTile = 0;

      this._settingsSections = {
        layout: _('Layout'),
        actionBoard: _('Action Boards'),
        map: _('Map'),
        company: _('Company'),
        other: _('Other'),
      };

      this._settingsConfig = {
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
          section: 'actionBoard',
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
          section: 'actionBoard',
        },
        actionBoardCostIcon: {
          default: 0,
          name: _('Costly Action Board Slots'),
          attribute: 'cost-icon',
          type: 'select',
          values: {
            0: _('Display credit cost'),
            1: _('Hide'),
          },
          section: 'actionBoard',
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
          section: 'layout',
        },
        orderBoard: {
          default: 0,
          name: _('Turn order board'),
          attribute: 'order-board',
          type: 'switch',
          section: 'layout',
        },
        fitAll: {
          default: 0,
          name: _('Scale down to fit everything on width screen'),
          type: 'switch',
          attribute: 'fitAll',
          section: 'layout',
        },
        companyBoardTwoColumns: {
          default: 0,
          name: _('Company boards layout'),
          section: 'layout',
          attribute: 'companyTwoColumns',
          type: 'select',
          values: {
            0: _('Always in one column'),
            1: _('Two columns if > 2'),
            2: _('Two columns only if > 3'),
          },
        },

        ownCompanyBoardLocation: {
          default: 2,
          name: _('My Company Board'),
          type: 'select',
          values: {
            0: _('In a floating collapsible container'),
            1: _('In a modal'),
            2: _('Next/below the map'),
          },
          section: 'layout',
        },
        otherCompanyBoardLocation: {
          default: 2,
          name: _('Other Company Boards'),
          type: 'select',
          values: {
            0: _('In a floating collapsible container'),
            1: _('In a modal'),
            2: _('Next/below the map'),
          },
          section: 'layout',
        },
        actionBoardScale: {
          default: 100,
          name: _('Action Boards scale'),
          type: 'slider',
          sliderConfig: {
            step: 5,
            padding: 0,
            range: {
              min: [40],
              max: [150],
            },
          },
          section: 'layout',
        },
        companyBoardScale: {
          default: 90,
          name: _('Company boards scale'),
          type: 'slider',
          sliderConfig: {
            step: 2,
            padding: 0,
            range: {
              min: [30],
              max: [100],
            },
          },
          section: 'layout',
        },
        mapScale: {
          default: 60,
          name: _('Map scale'),
          type: 'slider',
          sliderConfig: {
            step: 5,
            padding: 0,
            range: {
              min: [40],
              max: [100],
            },
          },
          section: 'layout',
        },
        energyTrackScale: {
          default: 100,
          name: _('Energy Track scale'),
          type: 'slider',
          sliderConfig: {
            step: 5,
            padding: 0,
            range: {
              min: [40],
              max: [150],
            },
          },
          section: 'layout',
        },
        fitAllScale: {
          default: [18, 56],
          name: _('Scales'),
          type: 'multislider',
          sliderConfig: {
            step: 2,
            padding: 0,
            range: {
              min: [10],
              max: [90],
            },
          },
          section: 'layout',
        },
        fitAllScaleEnergy: {
          default: [19, 52, 58],
          name: _('Scales'),
          type: 'multislider',
          sliderConfig: {
            step: 1,
            padding: 0,
            range: {
              min: [10],
              max: [90],
            },
          },
          section: 'layout',
        },

        map: {
          default: 1,
          name: _('Enhanced map display'),
          attribute: 'map',
          type: 'switch',
          section: 'map',
        },
        mapOverlay: {
          default: 50,
          name: _('Map white overlay'),
          type: 'slider',
          sliderConfig: {
            step: 5,
            padding: 0,
            range: {
              min: [0],
              max: [100],
            },
          },
          section: 'map',
        },
        zoneSeparators: {
          default: 1,
          name: _('Mountains/Hills/Plains separators'),
          attribute: 'separators',
          type: 'switch',
          section: 'map',
        },
        conduits: {
          default: 1,
          name: _('Conduit values'),
          attribute: 'conduit',
          type: 'switch',
          section: 'map',
        },
        headstream: {
          default: 1,
          name: _('Minimalist headstream tiles'),
          attribute: 'headstream',
          type: 'switch',
          section: 'map',
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
          section: 'company',
        },
        orderToken: {
          default: 1,
          name: _('Colored order token'),
          attribute: 'orderToken',
          type: 'switch',
          section: 'company',
        },
        altFr: {
          default: 0,
          name: _('Use purple color for France instead of white'),
          attribute: 'altFr',
          type: 'switch',
          section: 'company',
        },

        confirmMode: { type: 'pref', prefId: 103, section: 'other' },
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
          section: 'other',
        },
        waterAnimationSpeed: {
          default: 100,
          name: _('Water animation speed'),
          type: 'slider',
          sliderConfig: {
            step: 10,
            padding: 0,
            range: {
              min: [40],
              max: [260],
            },
          },
          section: 'other',
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
      this.setupTour();
      dojo.destroy('debug_output');

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
        'after'
      );
      // Create the "go to top" button
      dojo.place("<div id='go-to-top'></div>", $('active_player_statusbar'), 'before');
      $('go-to-top').addEventListener('click', () => window.scrollTo(0, 0));
      // Place energy track on top
      dojo.place('floating-energy-track-container', 'game_play_area', 'before');

      // Automa
      if (gamedatas.automa != null) {
        this.setupAutomaCards();
      }

      this.inherited(arguments);

      // Create round counter
      this._roundCounter = this.createCounter('round-counter');
      this.updateRoundCounter();
      this.updatePhase();
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
          'restartAction'
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
              action.resolve === false ? 'anytimeActions' : 'customActions'
            );
          });
        }
      }

      // Call appropriate method
      var methodName = 'onEnteringState' + stateName.charAt(0).toUpperCase() + stateName.slice(1);
      if (this[methodName] !== undefined) this[methodName](args.args);
    },

    onEnteringStateGameEnd(args) {
      dojo.style('floating-energy-track-container', 'margin-top', '60px');
    },

    onEnteringStateAutomaTurn(args) {
      this.addPrimaryActionButton('btnRunAutoma', _('Run'), () => this.takeAction('actRunAutoma', {}, false));
      let descs = args.actions.map((act) => {
        let action = act.action,
          result = act.result;
        let type = action.type;

        if (type == 'CONSTRUCT') {
          let typeDescs = {
            base: _('a Base'),
            elevation: _('an Elevation'),
            conduit: _('a Conduit'),
            powerhouse: _('a Powerhouse'),
          };

          let spaceId = 'ERROR';
          if (result != null) {
            spaceId = result.spaceId;
            let slot = this.getConstructSlot(spaceId);
            slot.classList.add('selected');
            $(`tech-tile-${result.tileId}`).classList.add('selected');
          }

          return this.fsr(_('construct ${structure} in ${spaceId}'), {
            i18n: ['structure'],
            structure: typeDescs[action.structure],
            spaceId: spaceId,
          });
        } else if (type == 'GAIN_MACHINE') {
          return this.fsr(_('gain ${resources}'), {
            resources: this.formatResourceArray(result),
          });
        } else if (type == 'ROTATE_WHEEL') {
          return this.fsr(_('rotate construction wheel by ${n} segment(s)'), {
            n: action.n,
          });
        } else if (type == 'TAKE_CONTRACT') {
          let msgs = {
            2: _('remove green contracts'),
            3: _('remove yellow contracts'),
            4: _('remove red contracts'),
          };
          let descs = [msgs[action.contract]];
          if (action.energy) {
            descs.push(this.fsr(_('gain ${n} energy'), { n: action.energy }));
          }
          return descs.join(', ');
        } else if (type == 'PATENT') {
          $(`tech-tile-${result.tileId}`).classList.add('selected');
          return _('take an advanced tile');
        } else if (type == 'PLACE_DROPLET') {
          result.locations.forEach((h) => {
            $(`headstream-tile-${h}`).dataset.n = parseInt($(`headstream-tile-${h}`).dataset.n || 0) + 1;
          });

          return this.fsr(_('place ${n} droplet(s) in headstream(s) ${hs}'), {
            n: result.locations.length,
            hs: result.locations.join(', '),
          });
        } else {
          return type;
        }
      });

      this.gamedatas.gamestate.description = _("Automa's turn: ") + descs.join(', ');
      this.updatePageTitle();
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
        this.addCustomTooltip('btnPower' + args.power[key].id, _(args.power[key].officer.description));
      });
    },

    updateRoundCounter() {
      let val = Math.min(5, this.gamedatas.round);
      this._roundCounter.toValue(val);
      $('ebd-body').dataset.round = val;
    },

    notif_startNewRound(n) {
      debug('Notif: starting new round', n);
      this.gamedatas.bonuses = n.args.bonuses;
      this.updateCompanyBonuses();
      this.gamedatas.round = n.args.round;
      this.updateRoundCounter();
    },

    updatePhase() {
      let phase = this.gamedatas.phase;
      let mapping = {
        pickStart: _('Advanced player setup'),
        income: _('Income phase'),
        headstream: _('Filling headstreams'),
        '': '',
        waterFlow: _('Water flow'),
        roundScoring: _('Round scoring'),
        endScoring: _('Final scoring'),
        endGame: _('End of the game'),
      };
      $('round-phase').innerHTML = mapping[phase];
    },

    notif_changePhase(n) {
      debug('Notif: changing phase', n);
      this.gamedatas.phase = n.args.phase;
      this.updatePhase();
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
        `<div id='brg-map-resizable' class="barrage-frame" data-map='${map.id}'>
          <div id='brg-map'>
            <div id="test-anim"></div>
            <div id='brg-zone-overlay'>
              <div></div>
              <div></div>
            </div>
          </div>
        </div>`,
        'map-wrapper'
      );
      let oMap = $('brg-map');

      let svgIds = {
        1: 'base-map-svg',
      };
      dojo.place($(svgIds[map.id]), oMap);

      // Headstreams
      Object.keys(map.headstreams).forEach((hId) => {
        let data = map.headstreams[hId];
        data.hId = hId;
        this.place('tplHeadstream', data, oMap);
        this.addCustomTooltip(`headstream-tile-${hId}`, this.tplHeadstream(data, true));
      });

      let clearHighlight = () => {
        dojo.query('.highlight').removeClass('highlight');
        [...$('base-map-svg').querySelectorAll('.highlight')].forEach((elt) => elt.classList.remove('highlight'));
      };

      // Conduits
      let mapPowerhouses = {};
      Object.keys(map.conduits).forEach((cId) => {
        let conduit = map.conduits[cId];
        conduit.cId = cId;
        let o = this.place('tplConduitSlot', conduit, oMap);
        o.addEventListener('mouseenter', () => {
          dojo.query(`.powerhouse-slot[data-zone="${conduit.end}"]`).addClass('highlight');
          dojo.query(`.powerhouse-zone[data-zone="${conduit.end}"]`).addClass('highlight');
          $('base-map-svg').querySelector(`#${cId}_P${conduit.end}`).classList.add('highlight');
        });
        o.addEventListener('mouseleave', clearHighlight);

        if (!mapPowerhouses[conduit.end]) mapPowerhouses[conduit.end] = [];
        mapPowerhouses[conduit.end].push(cId);
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
          let objs = [...$('brg-map').querySelectorAll(`.powerhouse-slot[data-zone="${zoneId}"]`)];
          objs.push(this.place('tplPowerhouseZone', { id: zoneId }, oMap));
          objs.forEach((o) => {
            o.addEventListener('mouseenter', () => {
              mapPowerhouses[zoneId].forEach((cId) => {
                dojo.query(`.conduit-slot[data-id="${cId}"]`).addClass('highlight');
                $('base-map-svg').querySelector(`#${cId}_P${zoneId}`).classList.add('highlight');
              });
            });
            o.addEventListener('mouseleave', clearHighlight);
          });
        }

        if (basinZones.includes(zoneId)) {
          this.place('tplBasinZone', { id: zoneId }, oMap);
        }
      });

      // Exits
      map.exits.forEach((exitId) => {
        this.place('tplExit', { id: exitId }, oMap);
      });
    },

    getConstructSlot(uid) {
      return $('brg-map').querySelector(`:not(.basin)[data-id='${uid}']`);
    },

    tplHeadstream(headstream, tooltip = false) {
      return `<div id="headstream-tile-${headstream.hId}${tooltip ? '-tooltip' : ''}" class='headstream' data-id='${
        headstream.hId
      }'>
        <div class='headstream-tile' data-tile='${headstream.tileId}'>
          <div class='headstream-tile-droplets'>
            <span>${headstream.droplets[0]}</span>
            <span>${headstream.droplets[1]}</span>
            <span>${headstream.droplets[2]}</span>
            <span>${headstream.droplets[3]}</span>
          </div>
        </div>
      </div>`;
    },

    tplConduitSlot(conduit) {
      return `<div class='conduit-slot' data-id='${conduit.cId}' data-production='${conduit.production}'></div>`;
    },

    tplPowerhouseSlot(powerhouse) {
      let cost = powerhouse.cost > 0 ? 'paying' : '';
      return `<div class='powerhouse-slot ${cost}' data-zone="${powerhouse.zone}" data-id='${powerhouse.id}'></div>`;
    },

    tplExit(exit) {
      return `<div class='rivier-exit' id='${exit.id}'></div>`;
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
    onChangeEnergyTrackSetting(val) {
      dojo.place(
        'energy-track-board-container',
        val == 1 ? 'map-energy-track-container' : 'floating-energy-track-resizable'
      );
      this.updateLayout();
    },

    setupEnergyTrack() {
      $('energy-track-btn').addEventListener('click', () =>
        $('floating-energy-track-container').classList.toggle('open')
      );

      let bonusTooltips = [
        _(
          'Score 2 Victory Points for each Contract you have fulfilled. Count all the Contract tiles (of any type) you have face down in your personal supply.'
        ),
        _('Score 4 Victory Points for each Base you have built.'),
        _('Score 4 Victory Points for each Elevation you have built.'),
        _('Score 4 Victory Points for each Conduit you have built.'),
        _('Score 5 Victory Points for each Powerhouse you have built.'),
        _(
          'Score 4 Victory Points for each Advanced Technology tile you have acquired. Count all the Advanced Technology tile in your personal supply and in your Construction Wheel. Basic Technology tiles do not count.'
        ),
        _('Score 5 Victory Points for each External Work you have fulfilled.'),
        _('Score 4 Victory Points for each Building you have built.'),
      ];

      let objectiveTooltips = [
        _('Count all the Bases and all the Powherouses you have built in building spaces with a red bordered icons.'),
        _(
          'Count all the structure pieces (of any type) in the area of the Map (Mountains, Hills or Plains) where you have built the most structure pieces.'
        ),
        _(
          'Count all your Bases connected by a Conduit of your color to a Powerhouse of your color. If there are two Bases connected to the same Powerhouse they both count.'
        ),
        _(
          'Count all the structure pieces (of any type) in the area of the Map (Mountains, Hills or Plains) where you have built the least structure pieces.'
        ),
        _(
          'Count all the basins where you have built at least one structure piece of any type. The maxium is 12 (one structure in all the twelve basins).'
        ),
        _(
          'Count all the basins where you have built at least three structure piece of any type. The maxium is 5 (three structures in five basins).'
        ),
      ];
      let objectiveTooltipsGeneric = _(
        "Determine the players' classification according to that condition. The first player scores 15 Victory Points, the second player scores 10 VPs and the third player scores 5VPs. In case of a tie, evenly divide the VPs of the respective tiers among the players who tied (round up if necessary)"
      );

      dojo.place('<div class="energy-track-portion"><div id="order-board"></div></div', 'energy-track-board');
      // Place bonus tiles
      for (let i = 0; i < 6; i++) {
        let portion = dojo.place('<div class="energy-track-portion"></div', 'energy-track-board');
        if (i == 0) {
          // First/second bonus
          let bonus = dojo.place('<div id="energy-track-first-second-bonus"><div></div><div></div></div>', portion);
          this.addCustomTooltip(
            bonus,
            _('The first player on the Energy Track scores 6 Victory Points; the second scores 2 Victory Points.')
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
            track
          );
        } else {
          let slot = dojo.place(
            `<div id='energy-track-${i}' class='energy-track-slot' data-i='${i}' ${bonus}></div>`,
            track
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
        if (board.id == 'officer' || board.id == 'company') {
          board.structure.forEach((row) => {
            let cId = row[0].cId;

            // Mahiri management to add the action space
            let container = document.querySelector(`.action-board[data-id='company-${cId}'] .action-board-inner`);
            if (board.id == 'officer') {
              container = $(`company-board-${cId}`).querySelector('.officer-symbol');
            }

            this.place('tplActionBoardRow', row, container);
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

      let costly = false;
      let slots = row.map((slot) => {
        if (slot['i'] != undefined) {
          let id = this.registerCustomTooltip(_(slot.t));
          return `<div id="${id}" class="action-board-icon">${this.formatString(slot.i)}</div>`;
        } else if (slot['type'] != undefined) {
          return '';
        } else {
          if (slot.cost > 0) costly = true;
          return this.tplActionSpace(slot);
        }
      });

      return `<div class='action-board-row ${costly ? 'costly' : ''}'>
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
        contentsTpl: `<div id='barrage-settings'>
          <div id='barrage-settings-header'></div>
          <div id="settings-controls-container"></div>
        </div>`,
      });
    },

    tplConfigPlayerBoard() {
      let automa = '';
      if (this.gamedatas.automa != null) {
        automa = `<div class="player_config_row" id='automa-cards-container'></div>`;
      }
      return `
<div class='player-board' id="player_board_config">
  <div id="player_config" class="player_board_content">

    <div class="player_config_row" id="round-counter-wrapper">
      ${_('Round')} <span id='round-counter'></span> / 5
    </div>
    <div class="player_config_row" id="round-phase"></div>

    <div class="player_config_row">
      <div id="tesla-help"></div>

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

    <div class="player_config_row" id='mahiri-add-XO'></div>
    ${automa}
  </div>
</div>
`;
    },

    onChangeMapScaleSetting(scale) {
      let elt = document.documentElement;
      elt.style.setProperty('--barrageMapScale', scale / 100);
    },

    onChangeMapOverlaySetting(scale) {
      let elt = document.documentElement;
      elt.style.setProperty('--barrageMapOverlay', scale / 100);
    },

    onChangeCompanyBoardScaleSetting(scale) {
      let elt = document.documentElement;
      elt.style.setProperty('--barrageCompanyBoardScale', scale / 100);
      $('modal-company-boards-wrapper').style.setProperty('--barrageCompanyBoardScale', (1.2 * scale) / 100);
    },

    onChangeEnergyTrackScaleSetting(scale) {
      if (this.settings && this.settings.energyTrack == 0) {
        let WIDTH = $('barrage-container').getBoundingClientRect()['width'] - 10;
        scale = Math.min(scale, (100 * WIDTH) / $('energy-track-board-container').offsetWidth);
      }
      let elt = document.documentElement;
      elt.style.setProperty('--barrageEnergyTrackScale', scale / 100);
    },

    onScreenWidthChange() {
      if (this.settings && this.settings.energyTrack == 0) {
        this.onChangeEnergyTrackSetting(this.settings.energyTrackScale);
      }
    },

    onChangeActionBoardScaleSetting(scale) {
      let elt = document.documentElement;
      elt.style.setProperty('--barrageActionBoardScale', scale / 100);
      $('action-boards-resizable').style.width = ($('action-boards-container').offsetWidth * scale) / 100 + 'px';
      $('action-boards-resizable').style.height = ($('action-boards-container').offsetHeight * scale) / 100 + 'px';
    },

    onChangeFitAllSetting(val) {
      this.updateLayout();
    },
    onChangeCompanyBoardTwoColumnsSetting(val) {
      this.updateLayout();
    },
    onChangeFitAllScaleSetting(val) {
      this.updateLayout();
    },
    onChangeFitAllScaleEnergySetting(val) {
      this.updateLayout();
    },
    onChangeOrderBoardSetting(val) {
      this.updateLayout();
    },

    updateLayout() {
      this.updateCompaniesLayout();
      if (this.settings.fitAll == 1) {
        if (!this.settings.fitAllScale || !this.settings.fitAllScaleEnergy) return;
        let WIDTH = $('barrage-container').getBoundingClientRect()['width'] - 25;

        const handles = this.settings.energyTrack == 0 ? this.settings.fitAllScale : this.settings.fitAllScaleEnergy;
        let proportions = [handles[0], handles[1] - handles[0], 100 - handles[1]];
        if (handles.length > 2) {
          proportions = [handles[0], handles[1] - handles[0], 100 - handles[2], handles[2] - handles[1]];
          WIDTH -= 15;
        }

        const TWO_COLUMNS =
          (this.settings.companyBoardTwoColumns == 1 && this.gamedatas.nCompanies > 2) ||
          (this.settings.companyBoardTwoColumns == 2 && this.gamedatas.nCompanies > 3);

        if (TWO_COLUMNS) {
          let total = 100 + proportions[2];
          proportions = proportions.map((p) => (p * 100) / total);
        }

        const actionBoardScale = (proportions[0] * WIDTH) / $('action-boards-container').offsetWidth;
        this.onChangeActionBoardScaleSetting(actionBoardScale);

        const mapScale = (proportions[1] * WIDTH) / $('brg-map').offsetWidth;
        this.onChangeMapScaleSetting(mapScale);

        const COMPANY_BOARD_WIDTH = 1150;
        const companyBoardScale = (proportions[2] * WIDTH) / COMPANY_BOARD_WIDTH;
        this.onChangeCompanyBoardScaleSetting(companyBoardScale);
        if (TWO_COLUMNS) {
          $('company-boards-container').style.width = (2 * proportions[2] * WIDTH) / 100 + 'px';
        } else {
          $('company-boards-container').style.width = 'auto';
        }

        if (proportions.length > 3) {
          const ENERGY_TRACK_WDITH = 136;
          const energyTrackScale = (proportions[3] * WIDTH) / ENERGY_TRACK_WDITH;
          this.onChangeEnergyTrackScaleSetting(energyTrackScale);
        } else {
          this.onChangeEnergyTrackScaleSetting(this.settings.energyTrackScale);
        }
      } else {
        this.onChangeActionBoardScaleSetting(this.settings.actionBoardScale);
        this.onChangeMapScaleSetting(this.settings.mapScale);
        this.onChangeCompanyBoardScaleSetting(this.settings.companyBoardScale);
        this.onChangeEnergyTrackScaleSetting(this.settings.energyTrackScale);
      }
    },

    setupAutomaCards() {
      this.place('tplAutomaCard', this.gamedatas.automa.front, 'automa-cards-container');
      this.place('tplAutomaCard', this.gamedatas.automa.back, 'automa-cards-container');
      this.updateAutomaCardTooltip(this.gamedatas.automa.front.id, this.gamedatas.automa.back.id);
    },

    tplAutomaCard(card) {
      let flipped = card.location == 'flipped' ? 'flipped' : '';
      return `<div class='automa-card ${flipped}' id="automa-card-${card.id}" data-id="${card.id}">
        <div class="card-back"></div>
      	<div class="card-front"></div>
      </div>`;
    },

    notif_flipAutomaCard(n) {
      debug('Notif: flipping a new automa card', n);
      let flipped = $('automa-cards-container').querySelector('.automa-card.flipped');
      let toFlip = $('automa-cards-container').querySelector('.automa-card:not(.flipped)');
      flipped.id += 'toDestroy';
      this.place('tplAutomaCard', { location: '', id: n.args.cardFront }, 'automa-cards-container');
      dojo.place(toFlip, 'automa-cards-container');
      toFlip.offsetHeight;
      toFlip.classList.add('flipped');
      if (this.isFastMode()) {
        flipped.remove();
      } else {
        setTimeout(() => flipped.remove(), 600);
      }
      this.updateAutomaCardTooltip(n.args.cardFront, n.args.cardBack);
    },

    updateAutomaCardTooltip(front, back) {
      this.addCustomTooltip(
        'automa-cards-container',
        `<div class='automa-card-tooltip'>
          ${front}
          ${this.tplAutomaCard({ id: front })}
          ${back}
          ${this.tplAutomaCard({ id: back, location: 'flipped' })}
        </div>`
      );
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
          disabled ? () => {} : () => this.takeAction('actChooseAction', { id: choice.id })
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
      this.onChangeAltFrSetting(this.settings.altFr);
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
          () => this.takeAtomicAction('actPlaceEngineer', [construct, n])
        );
      }

      if (args.mahiri !== null) {
        this.addPrimaryActionButton('btnUseMahiri', this.translate(_('Use Mahiri to copy another officer')), () =>
          this.takeAtomicAction('actPlaceEngineer', [args.mahiri, 1])
        );
      }

      if (args.canSkip) {
        this.addDangerActionButton('btnSkip', _('Skip turn'), () => {
          this.takeAction('actSkip');
        });
      }
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

    onEnteringStatePlaceEngineerChooseNumber(args) {
      this.addCancelStateBtn();
      $(args.uid).classList.add('selected');
      args.choices.forEach((choice) => {
        this.addPrimaryActionButton('btnChoice' + choice, choice, () =>
          this.takeAtomicAction('actPlaceEngineer', [args.uid, choice])
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
            if (currentSelection.length < args.n) {
              this.confirmationDialog(
                this.format_string_recursive(
                  _('Are you sure you want to place only ${placed} droplet(s) instead of ${n}?'),
                  { placed: currentSelection.length, n: args.n }
                ),
                () => {
                  this.takeAtomicAction('actPlaceDroplet', [currentSelection]);
                }
              );
            } else {
              this.takeAtomicAction('actPlaceDroplet', [currentSelection]);
            }
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
      let copying = false;
      let selectedTile = null;
      let selectedSpace = null;
      let tileCallback = () => {};
      let updateStatus = () => {
        tileIds.forEach((tileId) => {
          let elt = $(`tech-tile-${tileId}`);
          let selectable =
            (tileId == selectedTile || selectedTile == null) &&
            (selectedSpace == null || bySpace[selectedSpace].includes(tileId)) &&
            (!copying || args.antonPower.includes(tileId));
          elt.classList.toggle('selectable', selectable);
          elt.classList.toggle('selected', tileId == selectedTile);

          let elt2 = $(`tech-tile-${tileId}_summary`);
          if (elt2) {
            elt2.parentNode.classList.toggle('selectable', selectable);
            elt2.parentNode.classList.toggle('selected', tileId == selectedTile);
          }
        });
        spaceIds.forEach((spaceId) => {
          let elt = this.getConstructSlot(spaceId);
          elt.classList.toggle(
            'selectable',
            (spaceId == selectedSpace || selectedSpace == null) &&
              (selectedTile == null || byTile[selectedTile].includes(spaceId))
          );
          elt.classList.toggle('selected', spaceId == selectedSpace);
        });
        // Handle Anton
        if (args.antonId) {
          let tileId = args.antonId;
          let elt = $(`tech-tile-${tileId}`);
          elt.classList.toggle('selected', copying);
          let elt2 = $(`tech-tile-${tileId}_summary`);
          if (elt2) {
            elt2.parentNode.classList.toggle('selected', copying);
          }
        }

        dojo.destroy('btnConfirmConstruct');
        dojo.destroy('btnCancelConstruct');
        if (selectedTile != null || selectedSpace != null) {
          this.addSecondaryActionButton('btnCancelConstruct', _('Cancel'), () => {
            selectedTile = null;
            selectedSpace = null;
            copying = false;
            updateStatus();
          });
        }

        if (selectedTile != null && selectedSpace != null) {
          this.addPrimaryActionButton('btnConfirmConstruct', _('Confirm'), () =>
            this.takeAtomicAction('actConstruct', [selectedSpace, selectedTile, null])
          );
          this.changePageTitle('confirm');
        } else if (selectedTile != null) {
          this.changePageTitle('selectSpace');
        } else if (selectedSpace != null) {
          this.changePageTitle('selectTile');
        } else {
          this.changePageTitle();
        }
      };

      // Add listeners
      tileCallback = (tileId) => {
        let elt = $(`tech-tile-${tileId}`);
        if (!elt.classList.contains('selectable')) return;

        if (selectedTile == tileId) {
          selectedTile = null;
          copying = false;
        } else {
          selectedTile = tileId;
          copying = args.antonPower && args.antonPower.includes(tileId);

          if (byTile[tileId].length == 1) {
            selectedSpace = byTile[tileId][0];
          }
        }
        updateStatus();
      };

      tileIds.forEach((tileId) => {
        let elt = $(`tech-tile-${tileId}`);
        this.onClick(elt, () => tileCallback(tileId));
        let elt2 = $(`tech-tile-${tileId}_summary`);
        if (elt2) {
          this.onClick(elt2.parentNode, () => tileCallback(tileId));
        }
      });
      if (tileIds.length == 1) {
        tileCallback(tileIds[0]);
      } else if (args.antonCopied) {
        tileCallback(args.antonCopied);
      }

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

      updateStatus();
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
      let updateStatus = () => {
        dojo.query('#brg-map .selectable').removeClass('selectable selected');
        // Keep only available systems
        let possibleSystems = systems.filter(
          (system, i) =>
            (selectedConduit == null || byConduit[selectedConduit].includes(i)) &&
            (selectedPowerhouse == null || byPowerhouse[selectedPowerhouse].includes(i)) &&
            (selectedBasin == null || byBasin[selectedBasin].includes(i))
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
              dojo.string.substitute(_('Produce <ENERGY:${n}> with ${m} <WATER>'), { n: production, m: nDroplets })
            );
            this.addPrimaryActionButton('btnConfirmProduce' + nDroplets, msg, () =>
              this.takeAtomicAction('actProduce', [system.id, nDroplets])
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
      let listeningBasins = [],
        listeningPowerhouses = [],
        listeningConduits = [];
      systems.forEach((system) => {
        if (!listeningBasins.includes(system.basin)) {
          listeningBasins.push(system.basin);
          let basin = this.getConstructSlot(system.basin);
          this.onClick(basin, () => {
            if (!basin.classList.contains('selectable')) return;
            selectedBasin = selectedBasin == system.basin ? null : system.basin;
            updateStatus();
          });
        }

        if (!listeningPowerhouses.includes(system.powerhouseSpaceId)) {
          listeningPowerhouses.push(system.powerhouseSpaceId);
          let powerhouse = this.getConstructSlot(system.powerhouseSpaceId);
          this.onClick(powerhouse, () => {
            if (!powerhouse.classList.contains('selectable')) return;
            selectedPowerhouse = selectedPowerhouse == system.powerhouseSpaceId ? null : system.powerhouseSpaceId;
            updateStatus();
          });
        }

        if (!listeningConduits.includes(system.conduitSpaceId)) {
          listeningConduits.push(system.conduitSpaceId);

          let conduit = this.getConstructSlot(system.conduitSpaceId);
          this.onClick(conduit, () => {
            if (!conduit.classList.contains('selectable')) return;
            selectedConduit = selectedConduit == system.conduitSpaceId ? null : system.conduitSpaceId;
            updateStatus();
          });
        }
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
            this.gamedatas.contracts.stacks[i]
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
        this.slide(`contract-${contract.id}`, this.getContractContainer(contract));
        this._contractStackCounters[type].incValue(-1);
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
        reward: {},
      };

      this.gamedatas.bonuses = n.args.bonuses;
      this.updateCompanyBonuses();

      if (this.isFastMode()) {
        dojo.place(`contract-${contract.id}`, this.getContractContainer(contract));
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

    notif_emptyContractStack(n) {
      debug('Notif: removing contracts', n);
      n.args.contractIds.forEach((contractId) => {
        this.slide(`contract-${contractId}`, `contract-counter-${n.args.stack}`, {
          destroy: true,
        });
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

    ////////////////////////////
    //  _____
    // |_   _|__  _   _ _ __
    //   | |/ _ \| | | | '__|
    //   | | (_) | |_| | |
    //   |_|\___/ \__,_|_|
    //
    ////////////////////////////
    onLoadingComplete() {
      if (localStorage.getItem('barrageTour') != 1) {
        if (!this.isReadOnly()) this.showTour();
      } else {
        dojo.style('tour-slide-footer', 'display', 'none');
        $('neverShowMe').checked = true;
      }

      this.inherited(arguments);
    },

    setupTour() {
      this._tourModal = new customgame.modal('showTour', {
        class: 'barrageTour_popin',
        closeIcon: 'fa-times',
        openAnimation: true,
        openAnimationTarget: 'tesla-help',
        title: _('Barrage Tour'),
        contents: this.tplTourContent(),
        closeAction: 'hide',
        verticalAlign: 'flex-start',
      });

      dojo.connect($('tesla-help'), 'onclick', () => this.showTour());
      this.addTooltip('tesla-help', '', _('Show help tour.'));

      dojo.query('#tour-slider-container .tour-link').forEach((elt) => {
        let href = elt.getAttribute('href');
        dojo.connect(elt, 'click', () => this.setTourSlide(href));
      });

      dojo.connect($('neverShowMe'), 'change', function () {
        localStorage.setItem('barrageTour', this.checked ? 1 : 0);
      });
    },

    showTour() {
      this._tourModal.show();
      this.setTourSlide('intro');
    },

    setTourSlide(link) {
      dojo.query('#tour-slider-container .slide').addClass('inactive');
      dojo.removeClass('tour-slide-' + link, 'inactive');
    },

    tplTourContent() {
      let nextBtn = (link, text = null) =>
        `<div class='tour-btn'><button href="${link}" class="action-button bgabutton bgabutton_blue tour-link">${
          text == null ? _('Next') : text
        }</button></div>`;

      let introBubble = _(
        "Welcome to Barrage on BGA. I'm here to give you a tour of the interface, to make sure you'll enjoy your games to the fullest."
      );
      let introSectionUI = _('Global interface overview');
      let introSectionScoring = _('Scoring');
      let introSectionCards = _('Cards FAQ');
      let introSectionBugs = _('Report a bug');

      let panelInfoBubble = _("Let's start with this panel next to your name: a very handy toolbox.");
      let panelInfoItems = [
        _('round counter: useful to know how many turns left before the end of the game'),
        _('current phase: useful to know in which phase of the round/game you are currently playing'),
        _('my face: open this tour if you need it later'),
        _(
          "the switch will allow to toggle the safe/help mode: when this mode is enabled, clicking won't trigger any action and instead will open tooltips on any element with a question mark on it, making it sure you won't misclick"
        ),
        _(
          "settings: this implementation comes with a lot of ways to customize your experience to your needs. Take some time to play with them until you're comfortable"
        ),
        _(
          'the last line will only be present in 2 and 3 players game with Mahiri special officer in play, and will contain additional officer(s) that Mahiri can copy (in order to always have 3 other officers that she can copy)'
        ),
      ];

      let settingsBubble = _(
        'Here are various layouts that you can achieve using these settings. The settings are linked to your device so that you can customize as you like no matter where you play from.'
      );

      let playerPanelBubble = _('These player panels contain a lot of useful information!');
      let playerPanelItems = [
        _(
          'Next to your name: click on that icon to focus on the corresponding company board. Your current score and energy produced are also displayed under your name.'
        ),
        _(
          'First line: turn order, nation, executive officer, victory points you will get for the upcoming end of round bonus tile, number of "things" taken into accout for the objective tile. Each one of these has a tooltip with complementary information.'
        ),
        _('Second line: show how many resources you have in your reserve.'),
        _('Third line: show how many structures are on your company board.'),
        _(
          'Fourth line is split in two: your current company income is displayed on the left, and the number of fulfilled contracts is displayed on the right. You can click here to take a look at your fulfilled contracts.'
        ),
        _('Fifth line: contracts in your hand.'),
        _(
          'Last line: technology tiles in your hand, and a summary of your wheel (can be enabled/disabled in the settings).'
        ),
      ];
      let playerPanelRemark = _(
        'You can actually click anywhere on the player panel to make the interface focus on the corresponding company board, not just on the icon.'
      );

      let companyBoardBubble = _(
        'Depending on your settings, company board locations on your screen may vary, but they will always look like that and contains several crucial information such as the possible incomes.'
      );
      let companyBoardItems = [
        _(
          'On the bottom left, you have another reminder of your executive officer and his power, as well as your current resources in reserve.'
        ),
        _('On the top, you have your private engineer action spaces for the Construct action.'),
        _(
          'In the center part, you have additional important information: the unbuild structures, the cost to construct them, and the income you will get once they are built.'
        ),
        _('On the right, your construction wheel that will rotate in clockwise direction.'),
      ];
      let companyBoardRemark = _(
        "Don't forget that your nation's power will only take effect once you built your third powerhouse!"
      );

      let endOfTourBubble = _(
        'You should now know everything you need to enjoy this beautiful game on BGA! Have fun playing and please click on me if you need my tour again.'
      );

      let bugBubble = _('No code is error-free. Before reporting a bug, please follow the following steps.');
      let bugItems = [
        _('If the issue is related to a nation/officer, please indicate it clearly in the title'),
        _(
          'If your language is not English, please check the English description of the power of the nation/officer/advanced technology tile. If there is an incorrect translation to your language, please do not report a bug and use the translation module (Community > Translation) to fix it directly.'
        ),
        _(
          'When you encounter a bug, please refresh your page to see if the bug goes away. Knowing whether or not a bug is persisting through refresh or not will help us find and fix it, so please include that in the bug report!'
        ),
      ];
      let bugReport = _('Report a new bug');

      let neverShowMe = _('Never show me this tour again');

      var bugUrl = this.metasiteurl + '/bug?id=0&table=' + this.table_id;
      let addBubble = (text) =>
        `<div class="bubble"><div class='bubble-inner'><div class='bubble-content'>${text}</div></div></div>`;

      return `
      <div id="tour-slider-container">
        <div id="tour-slide-intro" class="slide">
            ${addBubble(introBubble)}
            <button href="panelInfo" class="action-button bgabutton bgabutton_blue tour-link">${introSectionUI}</button>
            <button href="bugs" class="action-button bgabutton bgabutton_red tour-link">${introSectionBugs}</button>
          </ul>
        </div>

        <div id="tour-slide-panelInfo" class="slide">
          ${addBubble(panelInfoBubble)}
          <div class="split-hor">
            <div>
              <div id="img-panelInfo" class="tour-img"></div>
            </div>
            <div>
              <ul>
                <li>${panelInfoItems[0]}</li>
                <li>${panelInfoItems[1]}</li>
                <li>${panelInfoItems[2]}</li>
                <li>${panelInfoItems[3]}</li>
                <li>${panelInfoItems[4]}</li>
                <li>${panelInfoItems[5]}</li>
              </ul>
            </div>
          </div>
          ${nextBtn('settings')}
        </div>

        <div id="tour-slide-settings" class="slide">
          ${addBubble(settingsBubble)}
          ${nextBtn('playerPanel')}
          <div class="tour-img" id="img-screenshot-1"></div>
          <div class="tour-img" id="img-screenshot-2"></div>
          <div class="split-hor">
            <div>
              <div class="tour-img" id="img-screenshot-3"></div>
            </div>
            <div>
              <div class="tour-img" id="img-screenshot-4"></div>
            </div>
          </div>
        </div>


        <div id="tour-slide-playerPanel" class="slide">
          ${addBubble(playerPanelBubble)}
          <div class="split-hor">
            <div>
              <div class="tour-img" id="img-player-panel"></div>
            </div>
            <div>
              <ul>
                <li>${playerPanelItems[0]}</li>
                <li>${playerPanelItems[1]}</li>
                <li>${playerPanelItems[2]}</li>
                <li>${playerPanelItems[3]}</li>
                <li>${playerPanelItems[4]}</li>
                <li>${playerPanelItems[5]}</li>
                <li>${playerPanelItems[6]}</li>
              </ul>
            </div>
          </div>
          <div class="tour-remark">${playerPanelRemark}</div>

          ${nextBtn('companyBoard')}
        </div>


        <div id="tour-slide-companyBoard" class="slide">
          ${addBubble(companyBoardBubble)}

          <div class="tour-img" id="img-company-board"></div>

          <ul>
            <li>${companyBoardItems[0]}</li>
            <li>${companyBoardItems[1]}</li>
            <li>${companyBoardItems[2]}</li>
            <li>${companyBoardItems[3]}</li>
          </ul>


          <div class="tour-remark">
            ${companyBoardRemark}
          </div>

          ${nextBtn('endOfTour')}
        </div>


        <div id="tour-slide-endOfTour" class="slide">
          ${addBubble(endOfTourBubble)}

          ${nextBtn('intro', _('Back'))}
        </div>


        <div id="tour-slide-bugs" class="slide">
          ${addBubble(bugBubble)}

          <ul>
            <li>${bugItems[0]}</li>
            <li>${bugItems[1]}</li>
            <li>${bugItems[2]}</li>
          </ul>

          <a href="${bugUrl}" class="action-button bgabutton bgabutton_red">${bugReport}</a>

          ${nextBtn('intro', _('Back'))}
        </div>

      </div>
      <div id="tour-slide-footer">
        <input type="checkbox" id="neverShowMe" />
        ${neverShowMe}
      </div>
    `;
    },
  });
});
