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
  const ALL_RESOURCES = RESOURCES.concat(PERSONAL_RESOURCES).concat(['fcontract']);

  return declare('barrage.companies', null, {
    forEachCompany(callback) {
      Object.values(this.gamedatas.companies).forEach((company) => callback(company));
    },

    setupCompanies() {
      this.forEachCompany((company) => this.setupCompany(company));
      this.setupCompaniesCounters();
      this.updateCompaniesOrder();
      this.updateCompanyBonuses();
      this.updateCompanyIncomes();
    },

    refreshCompanies() {
      this.forEachCompany((company) => {
        this.updateWheelAngle(company);
      });
      this.updateCompanyBonuses();
      this.updateCompanyIncomes();
    },

    updateCompaniesOrder() {
      // Compute number of companies and no offset to let player be on top
      let no = 0;
      let n = 0;
      this.forEachCompany((company) => {
        n++;
        if (company.pId == this.player_id) {
          no = company.no;
        }
      });

      this.forEachCompany((company) => {
        // TODO : handle automa
        $(`overall_player_board_${company.pId}`).style.order = 2 + ((company.no - no + n) % n);
      });
    },

    setupCompany(company) {
      company.color = COLOR_MAPPING[company.id];

      // Add player panel for Automas
      if (company.ai) {
        this.place('tplPlayerPanel', company, 'player_boards');
      }

      // Add additional info to player boards
      this.place('tplCompanyInfo', company, `player_panel_content_${company.color}`);
      $(`company-jump-to-${company.id}`).addEventListener('click', () => {
        window.scrollTo(0, $(`company-board-${company.id}`).getBoundingClientRect()['top'] - 30);
      });

      // Create company board
      this.place('tplCompanyBoard', company, 'company-boards-container');
      this.updateWheelAngle(company);
      this.addBoardIncomes(company);
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
        `officer-${company.id}`,
      );
      return (
        `<div class='company-info'>
        <div class='company-jump-to' id='company-jump-to-${company.id}'></div>
        <div class='company-no' id='company-position-${company.no}'>${no}</div>
        <div class='company-logo' data-company='${company.id}' style="border-color:#${company.color}"></div>
        <div class='officer-logo' data-officer='${company.officer.id}' id='officer-${company.id}'"></div>
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
          <div class='company-fulfilled-contracts'>
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
      const current = company.pId == this.player_id ? 'current' : '';

      return `<div class='company-board ${current}' data-company='${id}' id='company-board-${company.id}'>
        <div class='company-board-wrapper'>
          <div class='company-owner-wrapper'>
            <div class='company-owner' style='color:#${company.color}'>
              ${_(company.name)}
            </div>
          </div>

          <div class='action-board' data-id='company-${id}'>
            <div class='action-board-inner'></div>
          </div>

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

          this._companyCounters[company.id][res][anim ? 'toValue' : 'setValue'](value);
          dojo.attr(reserve.parentNode, 'data-n', value);
        });

        this._scoreCounters[company.id][anim ? 'toValue' : 'setValue'](company.score);
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
      this._scoreCounters[n.args.company_id].incValue(n.args.amount);
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
      });
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
        let container = $(`company-income-${company.id}`);
        container.innerHTML = '';

        let icons = incomes.icons.map((t) => this.formatString(t)).join('');
        dojo.place(icons, container);

        let desc = incomes.descs.map((t) => this.translate(t)).join('<br />');
        this.addCustomTooltip(container, desc);
      });
    },

    addBoardIncomes(company) {
      PERSONAL_RESOURCES.forEach((structure) => {
        let incomes = company.boardIncomes[structure];
        Object.keys(incomes).forEach((n) => {
          let income = incomes[n];
          let rank = (structure == 'powerhouse' ? 4 : 5) - n;
          let icons = income.i.map((t) => this.formatString(t));
          let desc = income.d.map((t) => this.translate(t)).join('<br />');

          let uid = this.registerCustomTooltip(desc);
          dojo.place(
            `<div class='company-income' id='${uid}'>${icons.join('')}</div>`,
            `${structure}-${rank}-${company.id}`,
          );
        });
      });
    },
  });
});
