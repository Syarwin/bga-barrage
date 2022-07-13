define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
  const COLOR_MAPPING = {
    1: 'be2748',
    2: '1b1b1b',
    3: '13757e',
    4: 'ffffff',
    5: 'ea4e1b',
  };

  const COMPANY_USA = 1;
  const COMPANY_GERMANY = 2;
  const COMPANY_ITALY = 3;
  const COMPANY_FRANCE = 4;
  const COMPANY_NETHERLANDS = 5;

  const RESOURCES = ['engineer', 'credit', 'excavator', 'mixer'];
  const PERSONAL_RESOURCES = ['base', 'elevation', 'conduit', 'powerhouse'];
  const ALL_RESOURCES = RESOURCES.concat(PERSONAL_RESOURCES).concat(['fcontract']);

  function arrayEquals(a, b) {
    return Array.isArray(a) && Array.isArray(b) && a.length === b.length && a.every((val, index) => val === b[index]);
  }

  return declare('barrage.companies', null, {
    forEachCompany(callback) {
      Object.values(this.gamedatas.companies).forEach((company) => callback(company));
    },

    setupCompanies() {
      this._companyContractsModals = {};
      this.forEachCompany((company) => this.setupCompany(company));
      this.setupCompaniesCounters();
      this.updateCompaniesOrder();
      this.updateCompanyBonuses();
      this.updateCompanyIncomes();

      this._companiesBoard = {};
      this._companiesModal = new customgame.modal('companyBoards', {
        class: 'barrage_popin',
        closeIcon: 'fa-times',
        closeAction: 'hide',
        verticalAlign: 'flex-start',
        onHide: () => (this._modalContainerOpen = null),
        contentsTpl: `<div id="modal-company-boards-wrapper">
          <div id="modal-company-buttons"></div>
          <div id="modal-company-slider">
            <div id="modal-company-boards-container"></div>
          </div>
        </div>`,
      });
      $('popin_companyBoards').addEventListener('click', () => this._companiesModal.hide());

      if (this.gamedatas.mahiriOfficers) {
        this.gamedatas.mahiriOfficers.forEach((officer, i) => {
          dojo.place(
            `<div class='officer-logo' data-officer='${officer.id}' id='mahiri-officer-${i}'></div>`,
            'mahiri-add-XO',
          );
          this.addCustomTooltip(`mahiri-officer-${i}`, `<h3>${_(officer.name)}</h3><p>${_(officer.description)}</p>`);
        });
      }
    },

    refreshCompanies() {
      this.forEachCompany((company) => {
        this.updateWheelAngle(company);
      });
      this.updateCompanyBonuses();
      this.updateCompanyIncomes();
    },

    setupCompany(company) {
      company.color = COLOR_MAPPING[company.id];

      // Add player panel for Automas
      if (company.ai) {
        this.place('tplPlayerPanel', company, 'player_boards');
      }

      // Add additional info to player boards
      this.place('tplCompanyInfo', company, `player_panel_content_${company.color}`);
      $(`overall_player_board_${company.pId}`).addEventListener('click', () => this.goToCompanyBoard(company));

      // Add energy counter
      dojo.place(
        ` <span id='energy-counter-${company.id}'></span> <i class="fa fa-bolt barrage-energy-counter"></i>`,
        `icon_point_${company.pId}`,
        'after',
      );

      // Create company board
      if ($(`company-board-${company.id}`)) {
        // Already existing = remove from pickStart
        $(`company-board-${company.id}`).querySelector('.company-owner').innerHTML = _(company.name);
        if (this.player_id == company.pId) {
          $(`company-board-${company.id}`).classList.add('current');
        }

        $('floating-company-boards-wrapper').dataset.n = 1;
        let container = this.getCompanyBoardContainer(company);
        let config = {};
        if (container == 'floating-company-boards-container') config.to = $('floating-company-boards-wrapper');
        this.slide(`company-board-${company.id}`, container, config).then(() => this.updateCompaniesLayout());
      } else {
        this.place('tplCompanyBoard', company, 'company-boards-container');
        this.updateWheelAngle(company);
        this.addBoardIncomes(company);
      }

      this._companyContractsModals[company.id] = new customgame.modal('companyFulfilledContracts' + company.id, {
        class: 'barrageContracts_popin',
        closeIcon: 'fa-times',
        closeAction: 'hide',
        verticalAlign: 'flex-start',
        title: this.format_string_recursive(_('Fulfilled contracts of ${company_name}'), {
          company_name: _(company.name),
        }),
        contentsTpl: `<div id="modal-company-contracts-${company.id}"></div>`,
      });
      dojo.place(`reserve_${company.id}_fcontract`, `modal-company-contracts-${company.id}`);
      this.onClick(`company-fulfilled-btn-${company.id}`, () => this._companyContractsModals[company.id].show(), false);

      // Handle Mahiri
      if (company.officer && company.officer.copied) {
        this.updateMahiriCopy(company.officer.copied.id);
      }
    },

    tplPlayerPanel(company) {
      // prettier-ignore
      return `<div id="overall_player_board_${company.pId}" class="player-board" style="border-color: ${company.color};">
          <div class="player_board_inner">
            <div class="emblemwrap is_premium" id="avatarwrap_2322021">
                <div class="avatar emblem automa" id="avatar_${company.id}"></div>
                <div class="emblemia"></div>
            </div>
            <div class="player-name" id="player_name_${company.pId}">
            	<span style="${company.color}">${_(company.name)}</span>
            </div>
            <div id="player_board_${company.pId}" class="player_board_content">
              <div class="player_score">
                <span id="player_score_${
                  company.pId
                }" class="player_score_value">0</span> <i class="fa fa-star" id="icon_point_${company.pId}"></i>
              </div>
            </div>
            <div id="player_panel_content_${company.color}" class="player_panel_content"></div>
          </div>
      </div>`;
    },

    tplCompanyInfo(company) {
      let nos = ['', 'I', 'II', 'III', 'IV', 'V'];
      let no = nos[company.no];

      //      <div class='company-name'>${_(this.getCompanyName(company.id))}</div>
      // TODO : handle no officer (for automas) + display description and icons
      this.registerCustomTooltip(
        `<h3>${_(company.officer.name)}</h3><p>${_(company.officer.description)}</p>`,
        `officer-${company.officer.id}`,
      );

      const SPECIAL_POWERS = {
        1: 'special_power_usa',
        2: 'special_power_germany',
        3: 'special_power_italy',
        4: 'special_power_france',
        5: 'special_power_netherlands',
      };
      this.registerCustomTooltip(
        `<h3>${this.getCompanyName(company.id)}</h3>${this.convertFlowToDescs({
          special_power: SPECIAL_POWERS[company.id],
        })}`,
        `company-logo-${company.id}`,
      );

      return (
        `<div class='company-info'>
        <div class='company-jump-to' id='company-jump-to-${company.id}'></div>
        <div class='company-no' data-company='${company.id}' id='company-position-${
          company.id
        }' style='color:#${this.getCompanyColor(company.id)}'>${no}</div>
        <div class='company-logo' data-company='${company.id}' style="border-color:#${
          company.color
        }" id='company-logo-${company.id}'"></div>
        <div class='officer-logo' data-officer='${company.officer.id}' id='officer-${company.officer.id}'></div>
        <div class='company-round-bonus' id='company-round-bonus-${company.id}'></div>
        <div class='company-obj-tile' id='company-obj-tile-${company.id}'></div>
      </div>
      <div class="company-panel-resources">
        <div class="company-reserve" id="reserve-${company.id}"></div>
      ` +
        RESOURCES.map((res) => this.tplResourceCounter(company, res)).join('') +
        `
        </div>
      </div>
      <div class='company-panel-personal-resources'>
      ` +
        PERSONAL_RESOURCES.map((res) => this.tplResourceCounter(company, res)).join('') +
        `
      </div>
      <div class='company-income-wrapper'>
          <div class='company-income' id='company-income-${company.id}'></div>
          <div class='company-fulfilled-contracts' id='company-fulfilled-btn-${company.id}'>
            ${this.tplResourceCounter(company, 'fcontract')}
          </div>
      </div>
      <div class='company-panel-contracts' id='company-contracts-${company.id}'>
      </div>
      <div class='company-panel-wheel-container'>
        <div class='company-panel-tech-tiles' id='company-tech-tiles-${company.id}'></div>
        <div class='company-summary-wheel'>
          <div class='summary-wheel-inner' id='summary-wheel-${company.id}'>
            <div class='summary-wheel-sector'></div>
            <div class='summary-wheel-sector'></div>
            <div class='summary-wheel-sector'></div>
            <div class='summary-wheel-sector'></div>
            <div class='summary-wheel-sector'></div>
            <div class='summary-wheel-sector'></div>
          </div>
        </div>
      </div>
      `
      );
    },

    tplResourceCounter(company, res, prefix = '', val = '') {
      let iconName = res.toUpperCase();
      let dataAttr = ['engineer', 'base', 'elevation', 'conduit', 'powerhouse'].includes(res)
        ? ` data-company='${company.id}'`
        : '';

      return `
        <div class='company-resource resource-${res}'>
          <span id='${prefix}resource_${company.id}_${res}' class='${prefix}resource_${res}'>${val}</span>
          <div class="meeple-container">
            <div class="barrage-meeple meeple-${res}"${dataAttr}>
            </div>
          </div>
          <div class='reserve' id='${prefix}reserve_${company.id}_${res}'></div>
        </div>
      `;
    },

    tplCompanyBoard(company) {
      const id = company.id;
      const current = company.pId == this.player_id ? 'current' : '';

      this.registerCustomTooltip(
        `<h3>${_(company.officer.name)}</h3><p>${_(company.officer.description)}</p>`,
        `officer-power-${company.officer.id}`,
      );
      this.registerCustomTooltip(
        `<h3>${_(company.officer.name)}</h3><p>${_(company.officer.description)}</p>`,
        `officer-logo-${company.officer.id}`,
      );

      return (
        `<div class='company-board ${current}' id='company-board-${company.id}'>
        <div class='company-board-resizable'>
          <div class='company-board-wrapper' data-company='${id}'>
            <div class='company-owner-wrapper'>
              <div class='company-owner' style='color:#${this.getCompanyColor(id)}'>
                ${_(company.name)}
              </div>
            </div>

            <div class='action-board' data-id='company-${id}'>
              <div class='action-board-inner'></div>
            </div>

            <div class="company-board-resources">
            ` +
        RESOURCES.map((res) =>
          this.tplResourceCounter(company, res, 'company_', company.officer.startingResources[res]),
        ).join('') +
        `
            </div>

            <div class='officer-symbol' data-officer='${company.officer.id}' id="officer-power-${company.officer.id}"></div>
            <div class='officer-logo' data-officer='${company.officer.id}' id="officer-logo-${company.officer.id}"></div>

            <div class='structures-wrapper bases-wrapper'>
              <div class='building-slot-header'></div>
              <div class='building-slot' id='base-4-${id}'></div>
              <div class='building-slot' id='base-3-${id}'></div>
              <div class='building-slot' id='base-2-${id}'></div>
              <div class='building-slot' id='base-1-${id}'></div>
              <div class='building-slot' id='base-0-${id}'></div>
            </div>

            <div class='structures-wrapper elevations-wrapper'>
              <div class='building-slot-header'></div>
              <div class='building-slot' id='elevation-4-${id}'></div>
              <div class='building-slot' id='elevation-3-${id}'></div>
              <div class='building-slot' id='elevation-2-${id}'></div>
              <div class='building-slot' id='elevation-1-${id}'></div>
              <div class='building-slot' id='elevation-0-${id}'></div>
            </div>

            <div class='structures-wrapper conduits-wrapper'>
              <div class='building-slot-header'></div>
              <div class='building-slot' id='conduit-4-${id}'></div>
              <div class='building-slot' id='conduit-3-${id}'></div>
              <div class='building-slot' id='conduit-2-${id}'></div>
              <div class='building-slot' id='conduit-1-${id}'></div>
              <div class='building-slot' id='conduit-0-${id}'></div>
            </div>

            <div class='structures-wrapper powerhouses-wrapper'>
              <div class='building-slot-header'></div>
              <div class='building-slot' id='powerhouse-3-${id}'></div>
              <div class='building-slot' id='powerhouse-2-${id}'></div>
              <div class='building-slot' id='powerhouse-1-${id}'></div>
              <div class='building-slot' id='powerhouse-0-${id}'></div>
            </div>
          </div>
          <div class='wheel-wrapper'>
            <div class='wheel' id='wheel-${company.id}' data-angle='${company.wheelAngle}'>
              <div class='wheel-sector'>
                <div class='wheel-tile-slot'></div>
                <div class='wheel-machineries-slots'></div>
              </div>
              <div class='wheel-sector'>
                <div class='wheel-tile-slot'></div>
                <div class='wheel-machineries-slots'></div>
              </div>
              <div class='wheel-sector'>
                <div class='wheel-tile-slot'></div>
                <div class='wheel-machineries-slots'></div>
              </div>
              <div class='wheel-sector'>
                <div class='wheel-tile-slot'></div>
                <div class='wheel-machineries-slots'></div>
              </div>
              <div class='wheel-sector'>
                <div class='wheel-tile-slot'></div>
                <div class='wheel-machineries-slots'></div>
              </div>
              <div class='wheel-sector'>
                <div class='wheel-tile-slot'></div>
                <div class='wheel-machineries-slots'></div>
              </div>
            </div>
          </div>
        </div>
      </div>`
      );
    },

    getCompanyScoreToken(companyId) {
      return $('energy-track').querySelector(`.meeple-score[data-company="${companyId}"]`);
    },

    getCompanyName(companyId) {
      const COMPANY_NAMES = {
        1: _('USA'),
        2: _('Germany'),
        3: _('Italy'),
        4: _('France'),
        5: _('Netherlands'),
      };

      return COMPANY_NAMES[companyId];
    },

    getCompanyColor(companyId) {
      const COMPANY_COLORS = {
        1: 'be2748',
        2: '1b1b1b',
        3: '13757e',
        4: 'ffffff',
        5: 'ea4e1b',
      };

      let c = COMPANY_COLORS[companyId];
      if (companyId == 4 && this.settings && this.settings.altFr == 1) {
        c = '922297';
      }

      return c;
    },

    onChangeAltFrSetting(val) {
      if (this.gamedatas.companies[4]) {
        let pId = this.gamedatas.companies[4].pId;
        if (pId > 0) {
          this.gamedatas.players[pId].color = val == 1 ? '922297' : 'ffffff';
          this.gamedatas.players[pId].color_back = val == 1 ? null : 'bbbbbb';
        }
        this.updatePageTitle();
      }
    },

    coloredCompanyName(name) {
      const company = Object.values(this.gamedatas.companies).find((company) => company.name == name);
      if (company == undefined) return '<!--PNS--><span class="playername">' + name + '</span><!--PNE-->';

      const color = this.getCompanyColor(company.id);
      let color_bg = ''; // TODO
      if (color == 'ffffff') {
        color_bg = 'background-color:#bbbbbb;';
      }
      return `<!--PNS--><span class="playername" style="color:#${color};${color_bg}">${_(name)}</span><!--PNE-->`;
    },

    notif_setupCompanies(n) {
      debug('Notif: initializing companies meeples', n);
      n.args.meeples.forEach((meeple) => this.addMeeple(meeple));
      n.args.tiles.forEach((tile) => this.addTechTile(tile));
      this.updateCompaniesCounters();
    },

    notif_produce(n) {
      debug('Notif: producing energy', n);
      this.gamedatas.bonuses = n.args.bonuses;
      this.updateCompanyBonuses();

      if (this.isFastMode()) return;

      let powerhouse = this.getConstructSlot(n.args.powerhouse);
      powerhouse.classList.add('producing');
      this.wait(100).then(() => {
        // Create temporary icon and slide it
        dojo.place(
          `<div id='produce-energy-counter'>${this.formatString('<ENERGY:' + n.args.energy + '>')}</div>`,
          powerhouse,
        );
        this.slide('produce-energy-counter', this.getCompanyScoreToken(n.args.company_id), {
          destroy: true,
          duration: 1350,
          phantom: false,
        }).then(() => {
          powerhouse.classList.remove('producing');
        });
      });
    },

    updateMahiriCopy(id = null) {
      const MAHIRI_ID = 12;
      if (id == null) {
        delete $(`officer-${MAHIRI_ID}`).dataset.copied;
        delete $(`officer-logo-${MAHIRI_ID}`).dataset.copied;
      } else {
        $(`officer-${MAHIRI_ID}`).dataset.copied = id;
        $(`officer-logo-${MAHIRI_ID}`).dataset.copied = id;
      }
    },

    notif_mahiriCopy(n) {
      debug('Notif: mahiri copy', n);
      this.updateMahiriCopy(n.args.officer_id);
    },

    notif_clearMahiri(n) {
      this.updateMahiriCopy();
    },

    /////////////////////////////////////////////
    //  _                            _
    // | |    __ _ _   _  ___  _   _| |_
    // | |   / _` | | | |/ _ \| | | | __|
    // | |__| (_| | |_| | (_) | |_| | |_
    // |_____\__,_|\__, |\___/ \__,_|\__|
    //             |___/
    /////////////////////////////////////////////
    getCompanyBoardContainer(company) {
      const containers = [
        'floating-company-boards-container',
        'modal-company-boards-container',
        'company-boards-container',
      ];
      let pref = this.settings[company.pId == this.player_id ? 'ownCompanyBoardLocation' : 'otherCompanyBoardLocation'];
      if (this.settings.fitAll == 1) {
        pref = 2;
      }
      return containers[pref];
    },

    updateCompaniesLayout() {
      if (!this.settings || !this.settings.ownCompanyBoardLocation || !this.settings.otherCompanyBoardLocation) return;

      // Remove buttons
      const btnContainers = ['floating-company-buttons', 'modal-company-buttons'];
      btnContainers.forEach((containerId) => ($(containerId).innerHTML = ''));

      let counters = [0, 0, 0];
      this.forEachCompany((company) => {
        dojo.place(`company-board-${company.id}`, this.getCompanyBoardContainer(company));
        let pref = this.settings[
          company.pId == this.player_id ? 'ownCompanyBoardLocation' : 'otherCompanyBoardLocation'
        ];
        if (this.settings.fitAll == 1) {
          pref = 2;
        }
        let index = counters[pref];
        counters[pref]++;

        if (btnContainers[pref]) {
          this.place('tplBtnCompanyBoard', company, btnContainers[pref]);
          $(`show-company-board-${company.id}`).addEventListener('click', (evt) => this.goToCompanyBoard(company, evt));
        }

        this._companiesBoard[company.id] = { index, pref };
      });

      // Update dataset to hide if 0
      $('floating-company-boards-wrapper').dataset.n = counters[0];
      $('company-boards-container').dataset.n = counters[2];

      this._floatingContainerOpen = null;
      this._modalContainerOpen = null;
    },

    tplBtnCompanyBoard(company) {
      let current = company.pId == this.player_id ? 'current' : '';
      return `<div id='show-company-board-${company.id}' class='company-board-button ${current}' data-company='${company.id}' style="border-color:#${company.color}">
        <i class="fa fa-times" aria-hidden="true"></i>
      </div>`;
    },

    goToCompanyBoard(company, evt = null) {
      if (evt) evt.stopPropagation();
      let t = this._companiesBoard[company.id];
      if (t.pref == 0) {
        // Floating container
        if (this._floatingContainerOpen == company.id) {
          delete $('floating-company-boards-wrapper').dataset.open;
          this._floatingContainerOpen = null;
        } else {
          $('floating-company-boards-wrapper').dataset.open = company.id;
          this._floatingContainerOpen = company.id;
        }
      } else if (t.pref == 1) {
        // Modal container
        if (this._modalContainerOpen == company.id) {
          delete $('modal-company-boards-wrapper').dataset.open;
          this._modalContainerOpen = null;
          this._companiesModal.hide();
        } else {
          $('modal-company-boards-wrapper').dataset.open = company.id;
          if (this._modalContainerOpen == null) {
            this._companiesModal.show();
          }
          this._modalContainerOpen = company.id;
        }
      } else if (t.pref == 2) {
        // Below map
        window.scrollTo(0, $(`company-board-${company.id}`).getBoundingClientRect()['top'] - 30);
      }
    },

    onChangeOwnCompanyBoardLocationSetting(val) {
      this.updateCompaniesLayout();
    },

    onChangeOtherCompanyBoardLocationSetting(val) {
      this.updateCompaniesLayout();
    },

    /////////////////////////////////////////////////////////////
    //  _____                    ___          _
    // |_   _|   _ _ __ _ __    / _ \ _ __ __| | ___ _ __
    //   | || | | | '__| '_ \  | | | | '__/ _` |/ _ \ '__|
    //   | || |_| | |  | | | | | |_| | | | (_| |  __/ |
    //   |_| \__,_|_|  |_| |_|  \___/|_|  \__,_|\___|_|
    /////////////////////////////////////////////////////////////
    updateCompaniesOrder() {
      // Compute number of companies and no offset to let player be on top
      let no = 0;
      let n = 0;
      let names = [];
      this.forEachCompany((company) => {
        n++;
        if (company.pId == this.player_id) {
          no = company.no;
        }
        names[company.no - 1] = company.no + ' : ' + _(company.name);
      });

      let desc = _('Current turn order :') + '<br />' + names.join('<br />');

      this.forEachCompany((company) => {
        // TODO : handle automa
        $(`overall_player_board_${company.pId}`).style.order = 2 + ((company.no - no + n) % n);
        $(`company-board-${company.id}`).style.order = 2 + ((company.no - no + n) % n);
        this.addCustomTooltip(`company-position-${company.id}`, desc);
      });
    },

    notif_updateTurnOrder(n) {
      debug('Notif: updating turn order', n);
      let nos = ['', 'I', 'II', 'III', 'IV', 'V'];

      n.args.order.forEach((cId, i) => {
        let no = i + 1;
        this.gamedatas.companies[cId].no = no;
        $(`company-position-${cId}`).innerHTML = nos[no];
      });
      this.updateCompaniesOrder();
    },

    ////////////////////////////////////////////////////
    //   ____                  _
    //  / ___|___  _   _ _ __ | |_ ___ _ __ ___
    // | |   / _ \| | | | '_ \| __/ _ \ '__/ __|
    // | |__| (_) | |_| | | | | ||  __/ |  \__ \
    //  \____\___/ \__,_|_| |_|\__\___|_|  |___/
    //
    ////////////////////////////////////////////////////
    /**
     * Create all the counters for company panels
     */
    setupCompaniesCounters() {
      this._companyCounters = {};
      this._scoreCounters = {};
      this._energyCounters = {};
      this.forEachCompany((company) => this.setupCompanyCounters(company));
      this.updateCompaniesCounters(false);
    },

    setupCompanyCounters(company) {
      this._companyCounters[company.id] = {};
      ALL_RESOURCES.forEach((res) => {
        this._companyCounters[company.id][res] = this.createCounter(
          `resource_${company.id}_${res}`,
          0,
          `company_resource_${company.id}_${res}`,
        );
      });

      this._energyCounters[company.id] = this.createCounter(`energy-counter-${company.id}`, company.energy);

      this._scoreCounters[company.id] = this.createCounter(
        company.isAI ? `company_score_${company.id}` : `player_score_${company.pId}`,
        company.score,
      );
    },

    /**
     * Update all the counters in company panels according to gamedatas
     */
    updateCompaniesCounters(anim = true) {
      this.forEachCompany((company) => {
        ALL_RESOURCES.forEach((res) => {
          let reserve = $(`reserve_${company.id}_${res}`);
          if (PERSONAL_RESOURCES.includes(res)) {
            reserve = $(`company-board-${company.id}`);
          }
          let meeples = reserve.querySelectorAll(res == 'fcontract' ? '.barrage-contract' : `.meeple-${res}`);
          let value = meeples.length;

          this._companyCounters[company.id][res].goTo(value, anim);
          dojo.attr(reserve.parentNode, 'data-n', value);
        });

        this._scoreCounters[company.id].goTo(company.score, anim);
        this._energyCounters[company.id].goTo(company.energy, anim);
      });
    },

    incEnergy(cId, n) {
      let current = +this.gamedatas.companies[cId].energy;
      current += n;
      this.gamedatas.companies[cId].energy = current;
      this._energyCounters[cId].toValue(current);
    },

    notif_incEnergy(n) {
      debug('Notif: increasing energy', n);
      let meeple = n.args.token;
      this.slide(`meeple-${meeple.id}`, this.getMeepleContainer(meeple));
      this.incEnergy(n.args.company_id, n.args.n);

      this.gamedatas.bonuses = n.args.bonuses;
      this.updateCompanyBonuses();
    },

    notif_resetEnergies(n) {
      debug('Notif: reset energies to 0', n);
      this.forEachCompany((company) => {
        this._energyCounters[company.id].toValue(0);
        this.gamedatas.companies[company.id].energy = 0;
      });
      n.args.tokens.forEach((token) => {
        $(`meeple-${token.id}`).dataset.flip = 0;
      });
      this.slideResources(n.args.tokens, {});
    },

    notif_score(n) {
      debug('Notif: updating scores', n);
      this._scoreCounters[n.args.company_id].toValue(n.args.total);
      let company = this.gamedatas.companies[n.args.company_id];
      if (!company.isAI) {
        this.scoreCtrl[company.pId].current_value = n.args.total;
        this.scoreCtrl[company.pId].target_value = n.args.total;
      }
    },

    ///////////////////////////////////////////////////
    //  ____  _      _      ____  _             _
    // |  _ \(_) ___| | __ / ___|| |_ __ _ _ __| |_
    // | |_) | |/ __| |/ / \___ \| __/ _` | '__| __|
    // |  __/| | (__|   <   ___) | || (_| | |  | |_
    // |_|   |_|\___|_|\_\ |____/ \__\__,_|_|   \__|
    //
    ///////////////////////////////////////////////////
    onEnteringStatePickStart(args) {
      let selectedMatchup = null;
      let selectedContract = null;
      let updateButton = () => {
        dojo.destroy('btnConfirmChoice');
        if (selectedMatchup != null && selectedContract != null) {
          this.addPrimaryActionButton('btnConfirmChoice', _('Confirm'), () =>
            this.takeAction('actPickStart', { matchup: selectedMatchup, contract: selectedContract }),
          );
        }
      };

      Object.keys(args.matchups).forEach((i) => {
        let matchup = args.matchups[i];
        let text = _(this.getCompanyName(matchup.company.id)) + ' & ' + _(matchup.officer.name);
        // Create company board if needed
        if (!$(`company-board-${matchup.company.id}`)) {
          matchup.company.name = text;
          matchup.company.officer = matchup.officer;
          this.place('tplCompanyBoard', matchup.company, 'pickStart-companies');
          this.addBoardIncomes(matchup.company);
        }

        // Add button if active
        if (this.isCurrentPlayerActive()) {
          this.addPrimaryActionButton('btnMatchup' + i, text, () => {
            if (selectedMatchup != null) {
              $(`btnMatchup${selectedMatchup}`).classList.remove('selected');
            }

            if (selectedMatchup == i) {
              selectedMatchup = null;
            } else {
              selectedMatchup = i;
              $(`btnMatchup${i}`).classList.add('selected');
            }
            updateButton();
          });
        }
      });

      this.attachRegisteredTooltips();

      Object.keys(args.contracts).forEach((contractId) => {
        let contract = args.contracts[contractId];
        this.addContract(contract);

        if (this.isCurrentPlayerActive()) {
          this.onClick(`contract-${contract.id}`, () => {
            if (selectedContract != null) {
              $(`contract-${selectedContract}`).classList.remove('selected');
            }

            if (selectedContract == contract.id) {
              selectedContract = null;
            } else {
              selectedContract = contract.id;
              $(`contract-${contract.id}`).classList.add('selected');
            }
            updateButton();
          });
        }
      });
    },

    notif_assignCompany(n) {
      debug('Notif: assigning company to someone', n);
      let company = n.args.datas;
      let cId = n.args.company_id;
      company.color = COLOR_MAPPING[company.id];
      let pId = n.args.player_id;
      ['player_board_inner_', 'player_panel_content_'].forEach((id) => {
        $(id + this.gamedatas.players[pId].color).id = id + company.color;
      });
      $(`player_name_${pId}`).querySelector('a').style.color = '#' + company.color;
      this.gamedatas.players[pId].color = company.color;
      this.gamedatas.companies[cId] = company;
      this.setupCompany(company);
      this.onChangeAltFrSetting(this.settings.altFr);
      this.setupCompanyCounters(company);
      this.updateCompaniesCounters();
      this.attachRegisteredTooltips();

      let container = document.querySelector(`.action-board[data-id='company-${cId}'] .action-board-inner`);
      n.args.actionSpaces.forEach((row) => {
        this.place('tplActionBoardRow', row, container);
      });

      container = $(`company-board-${cId}`).querySelector('.officer-symbol');
      n.args.actionSpacesXO.forEach((row) => {
        this.place('tplActionBoardRow', row, container);
      });

      n.args.meeples.forEach((meeple) => this.addMeeple(meeple));
      n.args.tiles.forEach((tile) => this.addTechTile(tile));
      this.updateCompaniesCounters();
    },

    //////////////////////////////////////////
    // __        ___               _
    // \ \      / / |__   ___  ___| |
    //  \ \ /\ / /| '_ \ / _ \/ _ \ |
    //   \ V  V / | | | |  __/  __/ |
    //    \_/\_/  |_| |_|\___|\___|_|
    //////////////////////////////////////////
    updateWheelAngle(company) {
      let wheel = $(`wheel-${company.id}`);
      let angle = parseInt(company.wheelAngle);
      wheel.dataset.angle = angle;
      wheel.style.transform = `rotate(${angle * 60}deg)`;
    },

    notif_rotateWheel(n) {
      debug('Notif: rotating wheel', n);
      let company = this.gamedatas.companies[n.args.company_id];
      company.wheelAngle += n.args.nb;
      this.updateWheelAngle(company);
      this.updateWheelSummary(company);
    },

    updateWheelSummaries() {
      this.forEachCompany((company) => this.updateWheelSummary(company));
    },

    updateWheelSummary(company) {
      let sectors = [...$(`wheel-${company.id}`).querySelectorAll('.wheel-sector')];
      let summarySectors = [...$(`summary-wheel-${company.id}`).querySelectorAll('.summary-wheel-sector')];
      sectors.forEach((sector, i) => {
        let container = summarySectors[i];
        dojo.empty(container);

        let tile = sector.querySelector('.wheel-tile-slot .barrage-tech-tile');
        if (tile) {
          let summaryTile = dojo.clone(tile);
          summaryTile.setAttribute('id', `${tile.id}_summary`);
          dojo.place(summaryTile, container);
          this.addCustomTooltip(`${tile.id}_summary`, this.tooltips[tile.id].label);
        }

        let meeples = [...sector.querySelectorAll('.wheel-machineries-slots .barrage-meeple')];
        let countByType = {};
        meeples.forEach((meeple) => {
          if (!countByType[meeple.dataset.type]) countByType[meeple.dataset.type] = 0;
          countByType[meeple.dataset.type]++;
        });
        Object.keys(countByType).forEach((type) => {
          let n = countByType[type];
          dojo.place(this.formatString(`<${type.toUpperCase()}_ICON:${n}>`), container);
        });
      });

      $(`summary-wheel-${company.id}`).dataset.angle = company.wheelAngle % 6;
    },

    ///////////////////////////////////////////////
    //  ____
    // | __ )  ___  _ __  _   _ ___  ___  ___
    // |  _ \ / _ \| '_ \| | | / __|/ _ \/ __|
    // | |_) | (_) | | | | |_| \__ \  __/\__ \
    // |____/ \___/|_| |_|\__,_|___/\___||___/
    ///////////////////////////////////////////////
    updateCompanyBonuses() {
      let objTileDesc = this.computeObjTileTooltip();
      this.forEachCompany((company) => {
        // Round bonus
        let roundBonus = this.gamedatas.bonuses[company.id].round;
        $(`company-round-bonus-${company.id}`).dataset.value = roundBonus.vp;
        let msg = _('No round bonus since energy produced is < 6');
        if (roundBonus.vp !== null) {
          msg = roundBonus.n + ' * ' + roundBonus.mult + _('VP');
          if (roundBonus.malus > 0) {
            msg += ' - ' + roundBonus.malus + _('VP');
          }
          let total = roundBonus.n * roundBonus.mult - roundBonus.malus;
          msg += ' = ' + total + _('VP');
          if (total < 0) {
            msg += '<br/>' + _('Players can never lose VPs from the Bonus tile scoring => 0VP');
          }
        }
        this.addCustomTooltip(`company-round-bonus-${company.id}`, msg);

        // Obj tile
        $(`company-obj-tile-${company.id}`).dataset.value = this.gamedatas.bonuses[company.id].obj;
        this.addCustomTooltip(`company-obj-tile-${company.id}`, objTileDesc);
      });
    },

    computeObjTileTooltip() {
      // Compute obj tile desc
      let desc = '';
      this.gamedatas.bonuses['objTile'].forEach((bonus) => {
        if (bonus.share == 0) {
          let names = bonus.cIds.map((cId) => this.coloredCompanyName(this.gamedatas.companies[cId].name)).join(', ');
          desc += this.format_string_recursive(_("${names} won't score any VP"), { names });
        }
        // No tie
        else if (bonus.cIds.length == 1) {
          let placeNames = {
            1: _('first place'),
            2: _('second place'),
            3: _('third place'),
          };

          let cId = bonus.cIds[0];
          desc +=
            this.format_string_recursive(_('${name} will earn ${n}VPs for ${place}'), {
              name: this.coloredCompanyName(this.gamedatas.companies[cId].name),
              n: bonus.share,
              place: placeNames[bonus.pos[0]],
            }) + '<br />';
        }
        // Tie
        else {
          let place = '';
          if (arrayEquals(bonus.pos, [1, 2])) place = _('first and second place');
          if (arrayEquals(bonus.pos, [2, 3])) place = _('second and third place');
          if (arrayEquals(bonus.pos, [1, 2, 3])) place = _('first, second and third place');

          let names = bonus.cIds.map((cId) => this.coloredCompanyName(this.gamedatas.companies[cId].name)).join(', ');
          desc +=
            this.format_string_recursive(_('${names} will share ${n}VPs for ${place}, gaining ${m}VPs each'), {
              names,
              n: bonus.vp,
              m: bonus.share,
              place,
            }) + '<br />';
        }
      });

      return desc;
    },

    //////////////////////////////////////////////////
    //  ___
    // |_ _|_ __   ___ ___  _ __ ___   ___  ___
    //  | || '_ \ / __/ _ \| '_ ` _ \ / _ \/ __|
    //  | || | | | (_| (_) | | | | | |  __/\__ \
    // |___|_| |_|\___\___/|_| |_| |_|\___||___/
    //
    //////////////////////////////////////////////////
    updateCompanyIncomes() {
      this.forEachCompany((company) => {
        let incomes = this.gamedatas.companies[company.id].incomes;
        let hasIncome = Object.keys(incomes).length > 0;
        let container = $(`company-income-${company.id}`);
        container.innerHTML = '';

        if (hasIncome) {
          let icons = this.convertFlowToIcons(incomes).join('');
          dojo.place(icons, container);

          let desc = this.convertFlowToDescs(incomes).join('<br />');
          this.addCustomTooltip(container, desc);
        }
      });
    },

    addBoardIncomes(company) {
      PERSONAL_RESOURCES.forEach((structure) => {
        let incomes = company.boardIncomes[structure];
        Object.keys(incomes).forEach((n) => {
          let income = incomes[n];
          let rank = (structure == 'powerhouse' ? 4 : 5) - n;
          let icons = this.convertFlowToIcons(income);
          let desc = this.convertFlowToDescs(income);

          let uid = this.registerCustomTooltip(desc);
          dojo.place(
            `<div class='company-income' id='${uid}'>${icons.join('')}</div>`,
            `${structure}-${rank}-${company.id}`,
          );
        });
      });
    },

    notif_updateIncome(n) {
      debug('Notif: updating income', n);
      this.gamedatas.companies[n.args.company_id].incomes = n.args.incomes;
      this.updateCompanyIncomes();
    },
  });
});
