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

  return declare('barrage.players', null, {
    forEachCompany(callback) {
      Object.values(this.gamedatas.companies).forEach((company) => callback(company));
    },

    setupCompanies() {
      this.forEachCompany((company) => {
        company.color = COLOR_MAPPING[company.id];

        // Add player board for Automas
        if (company.ai) {
          this.place('tplPlayerBoard', company, 'player_boards');
        }

        // Add additional info to player boards
        this.place('tplCompanyInfo', company, `player_panel_content_${company.color}`);

        // Create company board
        this.place('tplCompanyBoard', company, 'barrage-container');
      });
    },

    setupPlayers() {
      this.forEachPlayer((player) => {});
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
      return `<div class='company-info'>
        <div class='company-logo' data-company='${company.id}' style="border-color:#${company.color}"></div>
        <div class='company-name'>${_(this.getCompanyName(company.id))}</div>
      </div>`;
    },

    tplCompanyBoard(company) {
      const id = company.id;

      return `<div class='company-board' data-company='${id}'>
        <div class='company-board-wrapper'>
          <div class='company-owner'>
            ${_(company.name)}
          </div>

          <div class='action-space-wrapper construct-0' id='action-construct-0-${id}'>
            <div class='engineer-slot' id='construct-0-0-${id}'></div>
          </div>

          <div class='action-space-wrapper construct-1' id='action-construct-1-${id}'>
            <div class='engineer-slot' id='construct-1-0-${id}'></div>
            <div class='engineer-slot' id='construct-1-1-${id}'></div>
          </div>

          <div class='action-space-wrapper construct-2' id='action-construct-2-${id}'>
            <div class='engineer-slot' id='construct-2-0-${id}'></div>
            <div class='engineer-slot' id='construct-2-1-${id}'></div>
            <div class='engineer-slot' id='construct-2-2-${id}'></div>
          </div>

          <div class='action-space-wrapper construct-3' id='action-construct-3-${id}'>
            <div class='engineer-slot' id='construct-3-0-${id}'></div>
            <div class='engineer-slot' id='construct-3-1-${id}'></div>
            <div class='engineer-slot' id='construct-3-2-${id}'></div>
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
      </div>`;
    },
  });
});
