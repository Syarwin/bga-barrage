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

  const RESOURCES = ['credit', 'engineer', 'excavator', 'mixer'];
  const PERSONAL_RESOURCES = ['base', 'elevation', 'conduit', 'powerhouse'];
  const ALL_RESOURCES = RESOURCES;

  return declare('barrage.companies', null, {
    forEachCompany(callback) {
      Object.values(this.gamedatas.companies).forEach((company) => callback(company));
    },

    setupCompanies() {
      this.forEachCompany((company) => this.setupCompany(company));
      this.setupCompaniesCounters();
    },

    refreshCompanies() {
      this.forEachCompany((company) => {
        this.updateWheelAngle(company);
      });
    },

    setupCompany(company) {
      company.color = COLOR_MAPPING[company.id];

      // Add player board for Automas
      if (company.ai) {
        this.place('tplPlayerBoard', company, 'player_boards');
      }

      // Add additional info to player boards
      this.place('tplCompanyInfo', company, `player_panel_content_${company.color}`);

      // Create company board
      this.place('tplCompanyBoard', company, 'company-boards-container');
      this.updateWheelAngle(company);
    },

    tplPlayerBoard(company) {
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
        `officer-${company.id}`,
      );
      return (
        `<div class='company-info'>
        <div class='company-no' id='company-position-${company.no}'>${no}</div>
        <div class='company-logo' data-company='${company.id}' style="border-color:#${company.color}"></div>
        <div class='officer-logo' data-officer='${company.officer.id}' id='officer-${company.id}'"></div>
      </div>
      <div class="company-panel-resources">
        <div class="company-reserve" id="reserve-${company.id}"></div>
      ` +
        RESOURCES.map((res) => this.tplResourceCounter(company, res)).join('') +
        `
        </div>
      </div>
      <div class='company-panel-contracts' id='company-contracts-${company.id}'>
        <div class='company-fulfilled-contracts' id='company-fulfilled-contracts-${company.id}'></div>
      </div>
      <div class='company-panel-personal-resources'>
      ` +
        PERSONAL_RESOURCES.map((res) => this.tplResourceCounter(company, res)).join('') +
        `
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

    tplResourceCounter(company, res, prefix = '') {
      let iconName = res.toUpperCase();
      let dataAttr = ['engineer', 'base', 'elevation', 'conduit', 'powerhouse'].includes(res)
        ? ` data-company='${company.id}'`
        : '';

      return `
        <div class='company-resource resource-${res}'>
          <span id='${prefix}resource_${company.id}_${res}' class='${prefix}resource_${res}'></span>
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

      return `<div class='company-board' data-company='${id}'>
        <div class='company-board-wrapper'>
          <div class='company-owner'>
            ${_(company.name)}
          </div>

          <div class='action-space-wrapper construct-0 action-space' id='company-${id}-1'>
            <div class='action-space-slot' id='construct-0-0-${id}'></div>
          </div>

          <div class='action-space-wrapper construct-1 action-space' id='company-${id}-2'>
            <div class='action-space-slot' id='construct-1-0-${id}'></div>
            <div class='action-space-slot' id='construct-1-1-${id}'></div>
          </div>

          <div class='action-space-wrapper construct-2 action-space' id='company-${id}-3'>
            <div class='action-space-slot' id='construct-2-0-${id}'></div>
            <div class='action-space-slot' id='construct-2-1-${id}'></div>
            <div class='action-space-slot' id='construct-2-2-${id}'></div>
          </div>

          <div class='action-space-wrapper construct-3 action-space' id='company-${id}-4'>
            <div class='action-space-slot' id='construct-3-0-${id}'></div>
            <div class='action-space-slot' id='construct-3-1-${id}'></div>
            <div class='action-space-slot' id='construct-3-2-${id}'></div>
          </div>

          <div class='structures-wrapper bases-wrapper'>
            <div class='building-slot' id='base-4-${id}'></div>
            <div class='building-slot' id='base-3-${id}'></div>
            <div class='building-slot' id='base-2-${id}'></div>
            <div class='building-slot' id='base-1-${id}'></div>
            <div class='building-slot' id='base-0-${id}'></div>
          </div>

          <div class='structures-wrapper elevations-wrapper'>
            <div class='building-slot' id='elevation-4-${id}'></div>
            <div class='building-slot' id='elevation-3-${id}'></div>
            <div class='building-slot' id='elevation-2-${id}'></div>
            <div class='building-slot' id='elevation-1-${id}'></div>
            <div class='building-slot' id='elevation-0-${id}'></div>
          </div>

          <div class='structures-wrapper conduits-wrapper'>
            <div class='building-slot' id='conduit-4-${id}'></div>
            <div class='building-slot' id='conduit-3-${id}'></div>
            <div class='building-slot' id='conduit-2-${id}'></div>
            <div class='building-slot' id='conduit-1-${id}'></div>
            <div class='building-slot' id='conduit-0-${id}'></div>
          </div>

          <div class='structures-wrapper powerhouses-wrapper'>
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
      </div>`;
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

    coloredCompanyName(name) {
      const company = Object.values(this.gamedatas.companies).find((company) => company.name == name);
      if (company == undefined) return '<!--PNS--><span class="playername">' + name + '</span><!--PNE-->';

      const color = company.color;
      let color_bg = ''; // TODO
      if (color == 'ffffff') {
        color_bg = 'background-color:#bbbbbb;';
      }
      return `<!--PNS--><span class="playername" style="color:#${color};${color_bg}">${name}</span><!--PNE-->`;
    },

    /**
     * Create all the counters for company panels
     */
    setupCompaniesCounters() {
      this._companyCounters = {};
      this._scoreCounters = {};
      this.forEachCompany((company) => this.setupCompanyCounters(company));
      this.updateCompaniesCounters(false);
    },

    setupCompanyCounters(company) {
      this._companyCounters[company.id] = {};
      ALL_RESOURCES.forEach((res) => {
        this._companyCounters[company.id][res] = this.createCounter('resource_' + company.id + '_' + res);
      });
      this._scoreCounters[company.id] = this.createCounter('company_score_' + company.id);
    },

    /**
     * Update all the counters in company panels according to gamedatas
     */
    updateCompaniesCounters(anim = true) {
      this.forEachCompany((company) => {
        ALL_RESOURCES.forEach((res) => {
          let reserve = $(`reserve_${company.id}_${res}`);
          let meeples = reserve.querySelectorAll(`.meeple-${res}`);
          let value = meeples.length;

          this._companyCounters[company.id][res][anim ? 'toValue' : 'setValue'](value);
          dojo.attr(reserve.parentNode, 'data-n', value);
        });
      });
    },

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
        this.addPrimaryActionButton('btnMatchup' + i, _(matchup.cName) + ' & ' + _(matchup.xName), () => {
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
      });

      Object.keys(args.contracts).forEach((contractId) => {
        let contract = args.contracts[contractId];
        this.addContract(contract);
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
      });
    },

    notif_assignCompany(n) {
      debug('Notif: assigning company to someone', n);
      let company = n.args.datas;
      company.color = COLOR_MAPPING[company.id];
      let pId = n.args.player_id;
      ['player_board_inner_', 'player_panel_content_'].forEach((id) => {
        $(id + this.gamedatas.players[pId].color).id = id + company.color;
      });
      $(`player_name_${pId}`).querySelector('a').style.color = '#' + company.color;
      this.gamedatas.players[pId].color = company.color;
      this.gamedatas.companies[n.args.company_id] = company;
      this.setupCompany(company);
      this.setupCompanyCounters(company);
      this.updateCompaniesCounters();
    },

    notif_setupCompanies(n) {
      debug('Notif: initializing companies meeples', n);
      n.args.meeples.forEach((meeple) => this.addMeeple(meeple));
      this.updateCompaniesCounters();
    },

    notif_produce(n) {
      debug('Notif: producing energy', n);
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
        }).then(() => powerhouse.classList.remove('producing'));
      });
    },

    notif_score(n) {
      debug('Notif: updating scores', n);
    },

    notif_updateTurnOrder(n) {
      debug('Notif: updating turn order', n);
      // TODO: see how it can be placed before
      n.args.companies.forEach((company) => {
        this.slide(
          'company-position-' + company.no,
          $('overall_player_board_' + company.pId).getElementsByClassName('company-info')[0],
        );
      });
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
          summaryTile.setAttribute('id', tile.id + '_summary');
          dojo.place(summaryTile, container);
        }

        let meeples = [...sector.querySelectorAll('.wheel-machineries-slots .barrage-meeple')];
        let countByType = {};
        meeples.forEach((meeple) => {
          if (!countByType[meeple.dataset.type]) countByType[meeple.dataset.type] = 0;
          countByType[meeple.dataset.type]++;
        });
        Object.keys(countByType).forEach((type) => {
          let n = countByType[type];
          debug(this.formatString(`${n} <${type.toUpperCase()}_ICON>`));
          dojo.place(this.formatString(`<${type.toUpperCase()}_ICON:${n}>`), container);
        });
      });

      $(`summary-wheel-${company.id}`).dataset.angle = company.wheelAngle % 6;
    },
  });
});
