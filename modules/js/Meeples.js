define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
  const MEEPLES = ['ENGINEER', 'CREDIT', 'EXCAVATOR', 'MIXER'];
  const ICONS = [
    'PRODUCTION',
    'COST',
    'CREDIT',
    'PAY',
    'ARROW',
    'WATER',
    'WATER_DOWN',
    'ROTATE',
    'EXCAVATOR_ICON',
    'MIXER_ICON',
    'CONTRACT',
    'VP',
    'ANY_MACHINE',
    'ENERGY',
    'CONDUIT_X',
    'POWERHOUSE',
    'ELEVATION',
    'BASE',
    'BASE_PLAIN_HILL',
    'BASE_PLAIN',
    'CONSTRUCT',
  ];
  const PERSONAL_RESOURCES = ['BASE', 'ELEVATION', 'CONDUIT', 'POWHERHOUSE'];
  function isVisible(elem) {
    return !!(elem.offsetWidth || elem.offsetHeight || elem.getClientRects().length);
  }

  return declare('barrage.meeples', null, {
    setupMeeples() {
      // Bases first for display reason
      let meepleIds = this.gamedatas.bases.map((meeple) => {
        if (!$('meeple-' + meeple.id)) {
          this.addMeeple(meeple);
        }

        let o = $('meeple-' + meeple.id);
        let container = this.getMeepleContainer(meeple);
        if (o.parentNode != $(container)) {
          dojo.place(o, container);
        }

        return meeple.id;
      });

      // This function is refreshUI compatible
      meepleIds = this.gamedatas.meeples.map((meeple) => {
        if (!$('meeple-' + meeple.id)) {
          this.addMeeple(meeple);
        }

        let o = $('meeple-' + meeple.id);
        let container = this.getMeepleContainer(meeple);
        if (o.parentNode != $(container)) {
          dojo.place(o, container);
        }

        return meeple.id;
      });
      document.querySelectorAll('.barrage-meeple[id^="meeple-"]').forEach((oMeeple) => {
        if (!meepleIds.includes(parseInt(oMeeple.getAttribute('data-id')))) {
          dojo.destroy(oMeeple);
        }
      });
      this.updateResourcesHolders(false, false);
    },

    updateResourcesHolders(preAnimation, anim = true) {
      dojo
        .query('.resource-holder-update')
        .forEach((container) => dojo.attr(container, 'data-n', container.childNodes.length));
      this.updateCompaniesCounters(anim);
    },

    localUpdateResourcesHolders(meeple, leaving) {
      let parent = meeple.parentNode;
      if (parent.classList.contains('resource-holder-update')) {
        let n = parseInt(parent.getAttribute('data-n') || 0);
        parent.setAttribute('data-n', n + (leaving ? -1 : 1));
      } else if (parent.classList.contains('reserve')) {
        let t = parent.id.split('_');
        let n = parseInt(parent.parentNode.getAttribute('data-n') || 0);
        n += leaving ? -1 : 1;
        this._companyCounters[t[1]][t[2]].toValue(n);
        parent.parentNode.setAttribute('data-n', n);
      } else if (parent.classList.contains('building-slot')) {
        this._companyCounters[meeple.dataset.company][meeple.dataset.type].incValue(leaving ? -1 : 1);
      }

      let type = meeple.getAttribute('data-type');
    },

    addMeeple(meeple, location = null) {
      if ($('meeple-' + meeple.id)) return;
      this.place(
        'tplMeeple',
        meeple,
        location == null ? this.getMeepleContainer(meeple) : location,
        meeple.location == 'company' ? 'first' : null
      );
    },

    tplMeeple(meeple) {
      let className = '';

      let flip = '';
      if (meeple.type == 'score') {
        let n = +meeple.location.substr(13);
        if (n > 31) {
          flip = ' data-flip="0" ';
        }
      }

      return `<div class="barrage-meeple meeple-${meeple.type} ${className}" id="meeple-${meeple.id}" data-id="${meeple.id}" data-company="${meeple.cId}" data-type="${meeple.type}" ${flip}></div>`;
    },

    getMeepleContainer(meeple) {
      // Meeples in reserve (machines, credits)
      if (meeple.location == 'reserve') {
        let reserve = $(`reserve_${meeple.cId}_${meeple.type}`);
        if (reserve == null) {
          reserve = 'reserve-' + meeple.cId;
        }
        return reserve;
      }
      // Meeples in reserve on company boards
      else if (meeple.location == 'company') {
        return $(`${meeple.type}-${meeple.state}-${meeple.cId}`);
      }
      // Energy marker
      else if (meeple.type == 'score') {
        let n = +meeple.location.substr(13);
        if (n > 31) {
          n -= 30;
          if ($(`meeple-${meeple.id}`)) $(`meeple-${meeple.id}`).dataset.flip = '1';
        }
        if (n > 31) {
          n = 31;
        }
        return $(`energy-track-${n}`);
      }
      // Meeple on the wheel
      else if (meeple.location == 'wheel') {
        let n = 1 + parseInt(meeple.state);
        return $('wheel-' + meeple.cId).querySelector(`.wheel-sector:nth-of-type(${n}) .wheel-machineries-slots`);
      }
      // Meeples on action space (engineer)
      else if ($(meeple.location) && $(meeple.location).classList.contains('action-space')) {
        // Handle bank
        if (meeple.location == 'bank-b') return $('bank-b');

        let nChild = parseInt(meeple.state) + 1;
        return $(meeple.location).querySelector(`.action-space-slot:nth-of-type(${nChild})`);
      }
      // Base and elevation on map
      else if (
        ['base', 'elevation'].includes(meeple.type) &&
        $('brg-map').querySelector(`.dam-slot[data-id="${meeple.location}"]`)
      ) {
        return $('brg-map').querySelector(`.dam-slot[data-id="${meeple.location}"]`);
      }
      // Powerhouse on map
      else if (
        meeple.type == 'powerhouse' &&
        $('brg-map').querySelector(`.powerhouse-slot[data-id="${meeple.location}"]`)
      ) {
        return $('brg-map').querySelector(`.powerhouse-slot[data-id="${meeple.location}"]`);
      }
      // Conduit on map
      else if (meeple.type == 'conduit' && $('brg-map').querySelector(`.conduit-slot[data-id="${meeple.location}"]`)) {
        return $('brg-map').querySelector(`.conduit-slot[data-id="${meeple.location}"]`);
      }
      // Droplets
      else if (meeple.type == 'droplet') {
        return this.getDropletContainer(meeple.location);
      }

      console.error('Trying to get container of a meeple', meeple);
      return 'game_play_area';
    },

    getDropletContainer(location) {
      if (['HA', 'HB', 'HC', 'HD'].includes(location)) {
        return $('brg-map').querySelector(`.headstream[data-id="${location}"]`);
      } else if (location.substr(0, 4) == 'EXIT') {
        return $(location);
      } else if (location.indexOf('B') == 0) {
        return $('brg-map').querySelector(`.basin[data-id="${location}"]`);
      } else {
        return $('brg-map').querySelector(`[data-id="${location}"]`);
      }
    },

    /**
     * Wrap the sliding animations with a call to updateResourcesHolders() before and after the sliding is done
     */
    slideResources(meeples, configFn, syncNotif = true, updateHoldersAtEachMeeples = true) {
      let promises = meeples.map((resource, i) => {
        if (resource.hasOwnProperty('ignore') && resource.ignore == true) {
          return;
        }
        // Get config for this slide
        let config = typeof configFn === 'function' ? configFn(resource, i) : configFn;
        // Default delay if not specified
        let delay = config.delay ? config.delay : 100 * i;
        config.delay = 0;
        // Use meepleContainer if target not specified
        let target = config.target ? config.target : this.getMeepleContainer(resource);
        if (!this.isFastMode() && !isVisible(target)) {
          let cId = resource.cId;
          config.to = $(`show-company-board-${cId}`);
          if (!isVisible(config.to)) {
            config.to = $(`company-jump-to-${cId}`);
          }
        }

        // Slide it
        let slideIt = () => {
          // Create meeple if needed
          if (!$('meeple-' + resource.id)) {
            this.addMeeple(resource);
          } else {
            this.localUpdateResourcesHolders($('meeple-' + resource.id), true);
          }

          // Slide it
          if (!this.isFastMode() && !config.from && !isVisible($('meeple-' + resource.id))) {
            config.from = `resource_${resource.cId}_${resource.type}`;
          }
          return this.slide('meeple-' + resource.id, target, config);
        };

        // Update locally
        let updateCounters = () => {
          if (updateHoldersAtEachMeeples) {
            if ($('meeple-' + resource.id)) {
              this.localUpdateResourcesHolders($('meeple-' + resource.id), false);
            }
          }
        };

        if (this.isFastMode()) {
          slideIt();
          updateCounters();
          return null;
        } else {
          return this.wait(delay - 10)
            .then(slideIt)
            .then(updateCounters);
        }
      });

      // Update counters of receiving holders once all the promises are resolved
      let finalCounterUpdate = () => {
        if (!updateHoldersAtEachMeeples) {
          this.updateResourcesHolders(false);
        }

        if (syncNotif) {
          this.notifqueue.setSynchronousDuration(this.isFastMode() ? 0 : 10);
        }
      };

      if (this.isFastMode()) {
        finalCounterUpdate();
        return;
      } else
        return Promise.all(promises)
          .then(() => this.wait(10))
          .then(finalCounterUpdate);
    },

    /**
     * Placing engineers on action board
     */
    notif_placeEngineers(n) {
      debug('Notif: place engineers ', n);
      this.slideResources(n.args.engineers, {});
    },

    /**
     * Pay resources for an action
     */
    notif_payResources(n) {
      debug('Notif: paying resoures', n);
      this.slideResources(n.args.resources, (meeple) => ({
        target: $('page-title'),
        destroy: true,
        phantom: false,
      }));
    },

    /**
     * Pay resources to construction wheel
     */
    notif_payResourcesToWheel(n) {
      debug('Notif: paying to wheel', n);
      // Merge into one meeple list
      n.args.resources.forEach((meeple) => {
        meeple.delete = true;
        n.args.resources2.push(meeple);
      });

      let slideMeeples = () => {
        this.slideResources(n.args.resources2, (meeple) =>
          meeple.delete
            ? {
                target: $('page-title'),
                destroy: true,
                phantom: false,
              }
            : {}
        );
      };

      if (n.args.tile !== null) {
        let tile = n.args.tile;
        let config = {};
        let target = this.getTechTileContainer(tile);
        if (!this.isFastMode() && !isVisible(target)) {
          let cId = tile.cId;
          config.to = $(`show-company-board-${cId}`);
          if (!isVisible(config.to)) {
            config.to = $(`company-jump-to-${cId}`);
          }
        }

        this.slide(`tech-tile-${tile.id}`, target, config).then(slideMeeples);
      } else {
        slideMeeples();
      }
    },

    /**
     * Collect resources from a card
     */
    notif_collectResources(n) {
      debug('Notif: collecting resoures', n);
      this.slideResources(n.args.resources, {});
      if (n.args.tile) {
        this.slide(`tech-tile-${n.args.tile.id}`, this.getTechTileContainer(n.args.tile));
      }
    },

    /**
     * Recover resources from the wheel
     */
    notif_recoverResources(n) {
      debug('Notif: collecting resoures', n);
      let config = {};
      let cId = n.args.company_id;
      if (!this.isFastMode() && !isVisible($(`wheel-${cId}`))) {
        config.from = $(`show-company-board-${cId}`);
        if (!isVisible(config.from)) {
          config.from = $(`company-jump-to-${cId}`);
        }
      }
      let updateWheelSummary = () => {
        this.updateWheelSummary(this.gamedatas.companies[n.args.company_id]);
      };
      if (this.isFastMode()) {
        this.slideResources(n.args.resources, config);
        updateWheelSummary();
      } else {
        this.slideResources(n.args.resources, config).then(updateWheelSummary);
      }

      if (n.args.tile) {
        this.slide(`tech-tile-${n.args.tile.id}`, this.getTechTileContainer(n.args.tile), config);
      }
    },

    /**
     * Gain resources (create them from the reserve)
     */
    notif_gainResources(n) {
      debug('Notif: gain resoures', n);
      if (n.args.bonuses) {
        this.gamedatas.bonuses = n.args.bonuses;
        this.updateCompanyBonuses();
      }

      this.slideResources(n.args.resources, {
        from: n.args.spaceId ? n.args.spaceId : 'page-title',
      });
    },

    notif_flipToken(n) {
      debug('Notif: Flipping score token', n);
      if ($('meeple-' + n.args.token).classList.contains('meeple-score'))
        dojo
          .query('#meeple-' + n.args.token)
          .removeClass('meeple-score')
          .addClass('meeple-score-30');
      else {
        dojo
          .query('#meeple-' + n.args.token)
          .removeClass('meeple-score-30')
          .addClass('meeple-score');
      }
    },

    notif_construct(n) {
      debug('Notif: construct on the map', n);
      this.slideResources([n.args.meeple], {});
      this.gamedatas.bonuses = n.args.bonuses;
      this.updateCompanyBonuses();
    },

    notif_silentDestroy(n) {
      debug('Notif: destroying something silently', n);
      if (n.args.hasOwnProperty('resources')) {
        n.args.resources.forEach((meeple) => dojo.destroy('meeple-' + meeple.id));
      }
      if (n.args.hasOwnProperty('contracts')) {
        n.args.contracts.forEach((contract) => dojo.destroy('contract-' + contract));
      }
      if (n.args.hasOwnProperty('tiles')) {
        n.args.tiles.forEach((tile) => dojo.destroy('tech-tile-' + tile));
      }
      if (n.args.hasOwnProperty('bonusTile')) {
        dojo.destroy('bonus-tile-' + n.args.bonusTile);
      }
    },

    /**
     * Replace some expressions by corresponding html formating
     */
    formatString(str) {
      // This text icon are also board component, so we add the prefix _icon to distinguish them
      let conflictingNames = [];

      let jstpl_meeple = `
      <div class="meeple-container">
        <div class="barrage-meeple meeple-\${type}" data-company="\${company}">
        </div>
      </div>
      `;
      let companyId = 0;
      this.forEachCompany((company) => {
        if (this.gamedatas.gamestate.active_player == company.pId) {
          companyId = company.id;
        }
      });
      MEEPLES.forEach((name) => {
        let newName = name.toLowerCase() + (conflictingNames.includes(name) ? '_icon' : '');
        str = str.replace(
          new RegExp('<' + name + '>', 'g'),
          this.format_string(jstpl_meeple, { type: newName, company: companyId })
        );
      });

      let jstpl_icon = `
      <div class="icon-container icon-container-\${type}">
        <div class="barrage-icon icon-\${type}">\${text}</div>
      </div>
      `;
      ICONS.forEach((name) => {
        let newName = name.toLowerCase() + (conflictingNames.includes(name) ? '_icon' : '');
        str = str.replace(
          new RegExp('<' + name + ':([^>]+)>', 'g'),
          this.format_string(jstpl_icon, { type: newName, text: '<span>$1</span>' })
        );
        str = str.replace(
          new RegExp('<' + name + '>', 'g'),
          this.format_string(jstpl_icon, { type: newName, text: '' })
        );
      });

      str = str.replace(/\[([^\]]+)\]/gi, '<span class="text">$1</span>'); // Replace [my text] by <span clas="text">my text</span>
      str = str.replace(/\{\{([^\}]+)\}\}/gi, '<div class="text-wrapper">$1</div>'); // Replace {{my wrapped text}} by <div clas="text-wrapper">my wrapped text</div>
      return str;
    },

    /**
     * Return a string corresponding to an array of resources
     * [
     *   resourceType => amount,
     *   ...
     * ]
     */
    formatResourceArray(resources, formatMeeples = true) {
      let formated = [];
      Object.keys(resources).forEach((type) => {
        if (!MEEPLES.includes(type.toUpperCase())) return;

        let v = resources[type];
        formated.push((v > 1 ? v : '') + '<' + type.toUpperCase() + '>');
      });
      let desc = formated.join(',');
      return formatMeeples ? this.formatString(desc) : desc;
    },

    /**
     * Format log strings
     *  @Override
     */
    format_string_recursive(log, args) {
      try {
        if (log && args && !args.processed) {
          args.processed = true;

          let company_keys = Object.keys(args).filter((key) => key.substr(0, 12) == 'company_name');
          company_keys.forEach((key) => {
            args[key] = this.coloredCompanyName(args[key]);
          });

          // Representation of the class of a card
          if (args.resources_desc !== undefined) {
            args.resources_desc = this.formatString(args.resources_desc);
          }
          if (args.resources_desc2 !== undefined) {
            args.resources_desc2 = this.formatString(args.resources_desc2);
          }

          // Replace __str__ by italic wrapper
          log = log.replace(/__([^_]+)__/g, '<span class="action-card-name-reference">$1</span>');
        }
      } catch (e) {
        console.error(log, args, 'Exception thrown', e.stack);
      }

      return this.inherited(arguments);
    },

    /////////////////////////////////////////////////////////////////////////////
    //  _____ _                ____                          _
    // |  ___| | _____      __/ ___|___  _ ____   _____ _ __| |_ ___  _ __
    // | |_  | |/ _ \ \ /\ / / |   / _ \| '_ \ \ / / _ \ '__| __/ _ \| '__|
    // |  _| | | (_) \ V  V /| |__| (_) | | | \ V /  __/ |  | || (_) | |
    // |_|   |_|\___/ \_/\_/  \____\___/|_| |_|\_/ \___|_|   \__\___/|_|
    //
    /////////////////////////////////////////////////////////////////////////////

    convertFlowToDescs(income) {
      const mapping = {
        credit: _('Gain ${n} Credits.'),
        excavator: _('Gain ${n} Excavator(s).'),
        mixer: _('Gain ${n} Mixer(s).'),
        vp: _('Gain ${n} victory points.'),
        energy: _(
          'Move your Energy marker on the Energy Track by ${n} steps. You cannot use this amount of Energy Units to fulfill Contracts'
        ),
        any_machine: _('Gain ${n} Machinery(ies) of your choice.'),
        conduit: _(
          "Build a Conduit with a production value of ${n} (or less). You don't need to place Engineers, to insert the Technology tile or the Machineries."
        ),
        powerhouse: _('Place one of your Powerhouse in a free building space on the Map.'),
        elevation: _('Place one of your Elevation over one of your Dams.'),
        base: _('Place one of your Bases in a free building space on the Map.'),
        base_plain: _('Place one of your Bases in a free building space on the Plains.'),
        base_plain_hill: _('Place one of your Bases in a free building space on the Plains or on the Hills.'),
        // prettier-ignore
        flow_droplet: _('Place ${n} Water Drop(s) on Headstream tile(s) of your choice. These Water Drops flow immediately.'),
        ROTATE_WHEEL: _('Rotate your Construction Wheel by ${n} segment(s).'),
        PLACE_DROPLET: _(
          'Place ${n} Water Drop(s) on Headstream tile(s) of your choice. These Water Drops will flow during the Water Flow Phase.'
        ),
        production_bonus: _('Permanent bonus of +${n} on your productions.'),
      };

      let descs = [];
      Object.keys(income).forEach((t) => {
        let n = income[t];
        let desc = '';
        if (mapping[t]) {
          if (Array.isArray(n)) {
            let t2 = t + '_' + n.join('_').toLowerCase();
            if (mapping[t2]) {
              desc = mapping[t2];
            } else {
              desc = mapping[t];
            }
          } else {
            desc = this.translate({
              log: mapping[t],
              args: { n },
            });
          }
        } else if (t == 'special_power') {
          let speMapping = {
            special_power_usa: _(
              'In any phase of the round, if a Water Drop naturally flows through one of your Powerhouses, move your Energy marker by 1 step.'
            ),
            special_power_italy: _(
              'After you have performed a production action, move your Energy marker by 3 additional steps on the Energy track.'
            ),
            special_power_france: _(
              'You can fulfill every Contract (even the National Contracts) producting 3 Energy Units less than the Energy Units required by the Contract.'
            ),
            special_power_germany: _(
              'After you have performed a production action, you can perform a second production action using another Powerhouse. You must not apply the bonus/malus of the action symbol neither the bonus of your Company board.'
            ),
          };
          const warning = _('This unique ability becomes active only when you build your third Powerhouse');
          let icon = this.convertFlowToIcons({ special_power: n });
          desc = `<div class='tooltip-special-power'>${icon}</div>${_(speMapping[n])}<br /><b>${warning}</b>`;
        }

        descs.push(desc);
      });

      return descs;
    },

    convertFlowToIcons(income) {
      const mapping = {
        credit: 'CREDIT',
        excavator: 'EXCAVATOR_ICON',
        mixer: 'MIXER_ICON',
        vp: 'VP',
        energy: 'ENERGY',
        any_machine: 'ANY_MACHINE',
        conduit: 'CONDUIT_X',
        powerhouse: 'POWERHOUSE',
        elevation: 'ELEVATION',
        base: 'BASE',
        flow_droplet: 'WATER_DOWN',
        ROTATE_WHEEL: 'ROTATE',
        PLACE_DROPLET: 'WATER',
      };

      let icons = [];
      Object.keys(income).forEach((t) => {
        let n = income[t];
        let icon = '<CREDIT>';
        if (mapping[t]) {
          if (Array.isArray(n)) {
            icon = `<${mapping[t]}_${n.join('_').toUpperCase()}>`;
          } else {
            icon = `<${mapping[t]}:${n}>`;
          }
        } else if (t == 'production_bonus') {
          icon = `[+${n}]`;
        } else if (t == 'special_power') {
          icon = `<div class="icon-container icon-container-${n}">
            <div class="barrage-icon icon-${n}"></div>
          </div>`;
        }

        icons.push(this.formatString(icon));
      });

      return icons;
    },

    ////////////////////////////////////////////////////////////////////
    // __        __    _                 _          _
    // \ \      / /_ _| |_ ___ _ __     / \   _ __ (_)_ __ ___
    //  \ \ /\ / / _` | __/ _ \ '__|   / _ \ | '_ \| | '_ ` _ \
    //   \ V  V / (_| | ||  __/ |     / ___ \| | | | | | | | | |
    //    \_/\_/ \__,_|\__\___|_|    /_/   \_\_| |_|_|_| |_| |_|
    //
    ////////////////////////////////////////////////////////////////////

    notif_moveDroplets(n) {
      debug('Notif: moving droplets', n);
      // Fast mode => no need for fancy animations
      if (this.isFastMode()) {
        n.args.droplets.map((droplet, j) => {
          let oDroplet = $(`meeple-${droplet.id}`);
          let lastLocation = droplet.path[droplet.path.length - 1];
          if (lastLocation.substr(0, 4) == 'EXIT') oDroplet.remove();
          else dojo.place(oDroplet, this.getDropletContainer(lastLocation));
        });
        return;
      }

      Promise.all(
        n.args.droplets.map((droplet, j) => {
          let animatedPath = this.dropletComputePath(droplet);
          let animation = this.dropletComputeAnimation(droplet, animatedPath);
          let duration = (animatedPath.totalLength / this.settings.waterAnimationSpeed) * 400;
          return animation.start(duration, (j * 20000) / this.settings.waterAnimationSpeed);
        })
      ).then(() => {
        this.notifqueue.setSynchronousDuration(10);
      });
    },

    // Compute a container for an animated droplet (only different for powerhouses)
    dropletGetAnimationContainer(location) {
      if (location.indexOf('P') == 0) {
        // TODO : check without augmented map
        let zone = location.split('_')[0].substr(1);
        return $('brg-map').querySelector(`.powerhouse-zone[data-zone="${zone}"]`);
      } else {
        return this.getDropletContainer(location);
      }
    },

    // Convert a position in the svg to a position in the dom
    dropletGetSvgPathsScale() {
      return 0.67434362;
    },

    // Given a sequence of location, compute the corresponding positions and transitions
    dropletComputePath(droplet) {
      const SCALE = this.dropletGetSvgPathsScale();
      let oDroplet = $(`meeple-${droplet.id}`);
      let positions = [];
      let transitions = [];

      // Starting point
      let animatedMeeple = dojo.clone(oDroplet);
      dojo.attr(animatedMeeple, 'id', oDroplet.id + '_animated');
      dojo.place(animatedMeeple, 'brg-map');
      oDroplet.classList.add('phantom');
      positions.push({
        x: oDroplet.offsetLeft + oDroplet.offsetWidth / 2 + oDroplet.parentNode.offsetLeft,
        y: oDroplet.offsetTop + oDroplet.offsetHeight / 2 + oDroplet.parentNode.offsetTop,
      });

      // Intermediate positions
      let extractLocation = (location) => location.split('_')[0];
      droplet.path.forEach((location, i) => {
        if (i == 0) return;

        let oldLocation = extractLocation(droplet.path[i - 1]);
        let newLocation = extractLocation(location);
        let path = $('brg-map').querySelector('svg').querySelector(`#${oldLocation}_${newLocation}`);
        if (path) {
          transitions.push('slide');
          const start = path.getPointAtLength(0);
          positions.push({
            x: start.x * SCALE,
            y: start.y * SCALE,
          });
          transitions.push(path);
          const end = path.getPointAtLength(path.getTotalLength());
          positions.push({
            x: end.x * SCALE,
            y: end.y * SCALE,
          });
        }

        transitions.push('slide');
        if (i + 1 < droplet.path.length) {
          let container = this.dropletGetAnimationContainer(location);
          positions.push({
            x: container.offsetLeft + container.offsetWidth / 2,
            y: container.offsetTop + container.offsetHeight / 2,
          });
        }
      });

      // Last position
      let location = droplet.path[droplet.path.length - 1];
      let container = this.getDropletContainer(location);
      let endMeeple = dojo.clone(oDroplet);
      endMeeple.classList.add('phantom');
      dojo.attr(endMeeple, 'id', oDroplet.id + '_afterSlide');
      dojo.place(endMeeple, container);
      positions.push({
        x: endMeeple.offsetLeft + endMeeple.offsetWidth / 2 + container.offsetLeft,
        y: endMeeple.offsetTop + endMeeple.offsetHeight / 2 + container.offsetTop,
      });

      // positions.forEach((pos, i) => {
      //   dojo.place(`<div id='test-${i}' class="test"></div>`, 'brg-map');
      //   $('test-' + i).style.left = pos.x + 'px';
      //   $('test-' + i).style.top = pos.y + 'px';
      // });

      let path = { positions, transitions };
      path.lengths = this.dropletComputePathLengths(path);
      path.totalLength = path.lengths.reduce((c, t) => c + t, 0);
      return path;
    },

    // Compute for each transition the corresponding path length
    dropletComputePathLengths(path) {
      return path.transitions.map((type, i) => {
        if (type == 'slide') {
          let dx = path.positions[i].x - path.positions[i + 1].x;
          let dy = path.positions[i].y - path.positions[i + 1].y;
          return Math.sqrt(dx * dx + dy * dy);
        } else {
          return this.dropletGetSvgPathsScale() * type.getTotalLength();
        }
      });
    },

    // Interpolate position given u \in [0,1]
    dropletInterpolatePosition(path, u) {
      // Handle start and end of animation
      if (u <= 0) {
        return path.positions[0];
      } else if (u >= 1) {
        return path.positions[path.positions.length - 1];
      }

      // Find the index for which we go beyond the point where we are supposed to be
      let currentLength = 0;
      let currentIndex = 0;
      for (; currentIndex < path.transitions.length && currentLength < u * path.totalLength; currentIndex++) {
        currentLength += path.lengths[currentIndex];
      }

      // Compute remaining length from previous positions
      currentIndex--;
      currentLength -= path.lengths[currentIndex];
      let remainderLength = u * path.totalLength - currentLength;
      let lambda = remainderLength / path.lengths[currentIndex]; // \in [0, 1]
      if (path.transitions[currentIndex] == 'slide') {
        const prev = path.positions[currentIndex],
          next = path.positions[currentIndex + 1];
        return {
          x: prev.x + lambda * (next.x - prev.x),
          y: prev.y + lambda * (next.y - prev.y),
        };
      } else {
        const svgPath = path.transitions[currentIndex];
        const pos = svgPath.getPointAtLength(lambda * svgPath.getTotalLength());
        const SCALE = this.dropletGetSvgPathsScale();
        return {
          x: pos.x * SCALE,
          y: pos.y * SCALE,
        };
      }
    },

    // Now we initialize the animation
    dropletComputeAnimation(droplet, path) {
      let interpolatePosition = this.dropletInterpolatePosition.bind(this);
      let fadeOut = this.fadeOutAndDestroy.bind(this);

      return {
        start(duration, delay) {
          this.duration = duration;
          this.meeple = $(`meeple-${droplet.id}_animated`);
          this.resolve = null;
          const pos = interpolatePosition(path, 0);
          dojo.style(this.meeple, {
            position: 'absolute',
            zIndex: 10,
          });
          this.move(0);

          setTimeout(() => {
            this.tZero = Date.now();
            requestAnimationFrame(() => this.run());
          }, delay);
          return new Promise((resolve, reject) => {
            this.resolve = resolve;
          });
        },

        move(u) {
          const pos = interpolatePosition(path, u);
          this.meeple.style.left = pos.x - this.meeple.offsetWidth / 2 + 'px';
          this.meeple.style.top = pos.y - this.meeple.offsetHeight / 2 + 'px';

          // Compute rotation
          const posPrev = interpolatePosition(path, u - 0.01);
          const posNext = interpolatePosition(path, u + 0.01);
          const angle = -Math.atan2(posNext.x - posPrev.x, posNext.y - posPrev.y);
          this.meeple.style.transform = `rotate(${(angle * 180) / Math.PI}deg)`;
          this.meeple.style.transformOrigin = 'center center';
        },

        run() {
          const u = Math.min((Date.now() - this.tZero) / this.duration, 1);
          this.move(u);

          if (u < 1) {
            // Keep requesting frames, till animation is ready
            requestAnimationFrame(() => this.run());
          } else {
            this.onFinish();
          }
        },

        onFinish() {
          this.meeple.remove();
          dojo.place($(`meeple-${droplet.id}`), $(`meeple-${droplet.id}_afterSlide`), 'replace');
          $(`meeple-${droplet.id}`).classList.remove('phantom');
          // Destroy the droplet if on EXIT
          if ($(`meeple-${droplet.id}`).parentNode.classList.contains('rivier-exit')) {
            fadeOut($(`meeple-${droplet.id}`), 800);
          }
          if (this.resolve != null) {
            this.resolve();
          }
        },
      };
    },
  });
});
