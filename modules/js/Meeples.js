define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
  const MEEPLES = ['ENGINEER', 'CREDIT', 'EXCAVATOR', 'MIXER'];
  const ICONS = ['PRODUCTION', 'COST', 'CREDIT', 'ARROW', 'WATER', 'WATER_DOWN'];
  const PERSONAL_RESOURCES = []; //'farmer', 'fence', 'stable'];

  return declare('barrage.meeples', null, {
    setupMeeples() {
      // This function is refreshUI compatible
      let meepleIds = this.gamedatas.meeples.map((meeple) => {
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
        if (PERSONAL_RESOURCES.includes(t[2])) {
          leaving = !leaving;
        }

        let n = parseInt(parent.parentNode.getAttribute('data-n') || 0);
        n += leaving ? -1 : 1;
        this._playerCounters[t[1]][t[2]].toValue(n);
        parent.parentNode.setAttribute('data-n', n);
      }

      let type = meeple.getAttribute('data-type');
    },

    addMeeple(meeple, location = null) {
      if ($('meeple-' + meeple.id)) return;
      this.place('tplMeeple', meeple, location == null ? this.getMeepleContainer(meeple) : location);
    },

    tplMeeple(meeple) {
      let className = '';
      return `<div class="barrage-meeple meeple-${meeple.type} ${className}" id="meeple-${meeple.id}" data-id="${meeple.id}" data-company="${meeple.cId}" data-type="${meeple.type}"></div>`;
    },

    getMeepleContainer(meeple) {
      if (meeple.location == 'reserve') {
        let reserve = $(`reserve_${meeple.cId}_${meeple.type}`);
        if (reserve == null) {
          reserve = 'reserve-' + meeple.cId;
        }
        return reserve;
      } else if (meeple.location == 'company') {
        return $(`${meeple.type}-${meeple.state}-${meeple.cId}`);
      }

      console.error('Trying to get container of a meeple', meeple);
      return 'game_play_area';
    },

    /**
     * Wrap the sliding animations with a call to updateResourcesHolders() before and after the sliding is done
     */
    slideResources(meeples, configFn, syncNotif = true, updateHoldersAtEachMeeples = true) {
      let promises = meeples.map((resource, i) => {
        // Get config for this slide
        let config = typeof configFn === 'function' ? configFn(resource, i) : configFn;
        // Default delay if not specified
        let delay = config.delay ? config.delay : 100 * i;
        config.delay = 0;
        // Use meepleContainer if target not specified
        let target = config.target ? config.target : this.getMeepleContainer(resource);

        // Slide it
        let slideIt = () => {
          // Create meeple if needed
          if (!$('meeple-' + resource.id)) {
            this.addMeeple(resource);
          } else {
            this.localUpdateResourcesHolders($('meeple-' + resource.id), true);
          }

          // Slide it
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
     * Replace some expressions by corresponding html formating
     */
    formatString(str) {
      // This text icon are also board component, so we add the prefix _icon to distinguish them
      let conflictingNames = [];

      let jstpl_meeple = `
      <div class="meeple-container">
        <div class="barrage-meeple meeple-\${type}">
        </div>
      </div>
      `;
      MEEPLES.forEach((name) => {
        let newName = name.toLowerCase() + (conflictingNames.includes(name) ? '_icon' : '');
        str = str.replace(new RegExp('<' + name + '>', 'g'), this.format_string(jstpl_meeple, { type: newName }));
      });

      let jstpl_icon = `
      <div class="icon-container">
        <div class="barrage-icon icon-\${type}">\${text}</div>
      </div>
      `;
      ICONS.forEach((name) => {
        let newName = name.toLowerCase() + (conflictingNames.includes(name) ? '_icon' : '');
        str = str.replace(
          new RegExp('<' + name + ':([^>]+)>', 'g'),
          this.format_string(jstpl_icon, { type: newName, text: '<span>$1</span>' }),
        );
        str = str.replace(
          new RegExp('<' + name + '>', 'g'),
          this.format_string(jstpl_icon, { type: newName, text: '' }),
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

          // Representation of the class of a card
          if (args.resources_desc !== undefined) {
            args.resources_desc = this.formatString(args.resources_desc);
          }
          if (args.resources2_desc !== undefined) {
            args.resources2_desc = this.formatString(args.resources2_desc);
          }

          // Replace __str__ by italic wrapper
          log = log.replace(/__([^_]+)__/g, '<span class="action-card-name-reference">$1</span>');
        }
      } catch (e) {
        console.error(log, args, 'Exception thrown', e.stack);
      }

      return this.inherited(arguments);
    },
  });
});
