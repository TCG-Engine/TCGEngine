(function() {
  'use strict';

  var phaseNames = {
    SETUP_LOCATION: 'Choose Locations',
    SETUP_MULLIGAN: 'Mulligan',
    FEED_COLLECT: 'Feeding · Collect',
    FEED_BID: 'Feeding · Bid',
    FEED_RESOLVE: 'Feeding · Initiative',
    HORROR: 'Horror',
    REFRESH_READY: 'Refresh · Ready',
    REFRESH_FLIP: 'Refresh · Flip',
    REFRESH_HAND: 'Refresh · Cleanup'
  };

  function numberValue(value, fallback) {
    var parsed = parseInt(value, 10);
    return isNaN(parsed) ? fallback : parsed;
  }

  function setText(id, text) {
    var element = document.getElementById(id);
    if(element && element.textContent !== String(text)) element.textContent = String(text);
  }

  function ensureRenderedValue(id, value) {
    var element = document.getElementById(id);
    if(!element || element.textContent.trim() !== '') return;
    element.textContent = String(numberValue(value, 0));
  }

  function viewerPlayer() {
    var input = document.getElementById('playerID');
    var value = input ? numberValue(input.value, 0) : numberValue(window.currentPlayerIndex, 0);
    return value === 1 || value === 2 ? value : numberValue(window.viewerPerspective, 1);
  }

  function installCardBack() {
    if(typeof window.resolveCardImageID !== 'function' || window.__hellbreakCardBackInstalled) return;
    var baseResolver = window.resolveCardImageID;
    window.resolveCardImageID = function(cardID) {
      if(cardID === 'CardBack') return 'DOT_001_back';
      return baseResolver(cardID);
    };
    window.__hellbreakCardBackInstalled = true;
  }

  function parseVariables() {
    var raw = window.DecisionQueueVariablesData;
    if(raw == null || raw === '') return {};
    try {
      var parsed = JSON.parse(String(raw));
      return parsed && typeof parsed === 'object' ? parsed : {};
    } catch(error) {
      return {};
    }
  }

  function parseLog() {
    var parsed = parseVariables();
    return Array.isArray(parsed.HellbreakPublicLog) ? parsed.HellbreakPublicLog : [];
  }

  function renderHistory() {
    var list = document.getElementById('hbHistoryList');
    if(!list) return;
    var entries = parseLog().slice(-12).reverse();
    var signature = JSON.stringify(entries);
    if(list.getAttribute('data-signature') === signature) return;
    list.setAttribute('data-signature', signature);
    list.replaceChildren();
    if(entries.length === 0) {
      var empty = document.createElement('li');
      empty.className = 'hb-history-empty';
      empty.textContent = 'The match is just beginning.';
      list.appendChild(empty);
      return;
    }
    entries.forEach(function(entry) {
      var item = document.createElement('li');
      item.className = 'hb-history-' + String(entry.type || 'info').toLowerCase().replace(/[^a-z0-9_-]/g, '');
      var round = document.createElement('span');
      round.textContent = 'R' + numberValue(entry.round, 1);
      item.appendChild(round);
      item.appendChild(document.createTextNode(String(entry.message || 'Game event')));
      list.appendChild(item);
    });
  }

  function updateWinner(viewer) {
    var winner = numberValue(window.WinnerData, 0);
    var overlay = document.getElementById('hbVictory');
    if(!overlay) return;
    if(winner !== 1 && winner !== 2) {
      overlay.hidden = true;
      return;
    }
    var spectator = viewer !== 1 && viewer !== 2;
    setText('hbVictoryTitle', spectator ? 'Player ' + winner + ' Wins' : (winner === viewer ? 'Victory' : 'Defeat'));
    setText('hbVictoryCopy', 'Player ' + winner + ' revealed the opponent’s final Health card.');
    overlay.hidden = false;
  }

  var directActionsByCard = {};
  var directActionBusy = false;
  var legacyHorrorMenuOpen = false;

  function pendingDecision() {
    try {
      var queue = typeof window.ParseDecisionQueue === 'function'
        ? window.ParseDecisionQueue(window.myDecisionQueueData || '') : [];
      if(!Array.isArray(queue)) return null;
      for(var index = 0; index < queue.length; ++index) {
        if(queue[index] && !queue[index].removed) return queue[index];
      }
    } catch(error) {}
    return null;
  }

  function isHorrorActionPrompt() {
    var decision = pendingDecision();
    if(!decision || String(decision.Type || '').toUpperCase() !== 'MZMODAL') return false;
    return String(decision.Tooltip || '').replace(/_/g, ' ').trim().toLowerCase() === 'choose your horror action';
  }

  function addDirectAction(mzID, descriptor) {
    if(!mzID) return;
    if(!directActionsByCard[mzID]) directActionsByCard[mzID] = [];
    directActionsByCard[mzID].push(descriptor);
  }

  function clearDirectCards() {
    document.querySelectorAll('.hb-direct-action-card').forEach(function(card) {
      card.classList.remove('hb-direct-action-card');
      card.removeAttribute('data-hb-action-label');
      card.removeAttribute('role');
      card.removeAttribute('tabindex');
      if(card.hasAttribute('data-hb-original-title')) {
        var original = card.getAttribute('data-hb-original-title');
        if(original) card.setAttribute('title', original);
        else card.removeAttribute('title');
        card.removeAttribute('data-hb-original-title');
      }
    });
  }

  function hideCardActionMenu() {
    var menu = document.getElementById('hbCardActionMenu');
    if(menu) menu.remove();
  }

  function submitDirectAction(descriptor, mzID) {
    if(directActionBusy || typeof window.SubmitInput !== 'function') return;
    directActionBusy = true;
    legacyHorrorMenuOpen = false;
    hideCardActionMenu();
    var dock = document.getElementById('hbHorrorActionDock');
    if(dock) dock.classList.add('is-busy');
    var payload = ['DIRECT', descriptor.id, mzID || '', Number(descriptor.abilityIndex == null ? -1 : descriptor.abilityIndex)].join('|');
    window.SubmitInput('10001', '&cardID=' + encodeURIComponent('BoardAction!CustomInput!' + payload));
    window.setTimeout(function() {
      directActionBusy = false;
      scheduleUpdate();
    }, 1000);
  }

  function showCardActionMenu(card, actions) {
    hideCardActionMenu();
    var menu = document.createElement('div');
    menu.id = 'hbCardActionMenu';
    menu.className = 'hb-card-action-menu';
    menu.setAttribute('role', 'menu');
    actions.forEach(function(action) {
      var button = document.createElement('button');
      button.type = 'button';
      button.textContent = action.label;
      button.addEventListener('click', function(event) {
        event.stopPropagation();
        submitDirectAction(action, card.getAttribute('data-mzid') || card.id || '');
      });
      menu.appendChild(button);
    });
    document.body.appendChild(menu);
    var rect = card.getBoundingClientRect();
    var menuRect = menu.getBoundingClientRect();
    menu.style.left = Math.max(8, Math.min(window.innerWidth - menuRect.width - 8, rect.left + rect.width / 2 - menuRect.width / 2)) + 'px';
    menu.style.top = Math.max(8, rect.top - menuRect.height - 8) + 'px';
  }

  function ensureHorrorActionDock() {
    var dock = document.getElementById('hbHorrorActionDock');
    if(dock) return dock;
    dock = document.createElement('div');
    dock.id = 'hbHorrorActionDock';
    dock.className = 'hb-horror-action-dock';
    dock.setAttribute('role', 'toolbar');
    dock.setAttribute('aria-label', 'Horror actions');
    dock.innerHTML = '<span class="hb-horror-action-help">Click a highlighted card to act</span>' +
      '<button type="button" data-action="SLUMBER">Slumber <small>+1 malice</small></button>' +
      '<button type="button" data-action="PASS">Pass</button>' +
      '<button type="button" class="secondary" data-action="LIST">Action list</button>';
    dock.addEventListener('click', function(event) {
      var button = event.target.closest('button[data-action]');
      if(!button || button.disabled) return;
      var action = button.getAttribute('data-action');
      if(action === 'LIST') {
        legacyHorrorMenuOpen = true;
        var modal = document.getElementById('mzmodal-overlay');
        if(modal) modal.classList.remove('hb-direct-hidden');
        return;
      }
      submitDirectAction({ id: action, label: action === 'SLUMBER' ? 'Slumber' : 'Pass' }, '');
    });
    var summary = document.querySelector('#hbMySide .hb-player-summary');
    (summary || document.body).appendChild(dock);
    return dock;
  }

  function updateDirectHorrorActions(viewer, phase, priority) {
    var promptActive = phase === 'HORROR' && priority === viewer && isHorrorActionPrompt();
    var modal = document.getElementById('mzmodal-overlay');
    if(modal) {
      var title = modal.querySelector('.mzmodal-title');
      var isActionModal = title && title.textContent.trim().toLowerCase() === 'choose your horror action';
      if(isActionModal) modal.classList.toggle('hb-direct-hidden', promptActive && !legacyHorrorMenuOpen);
    }

    clearDirectCards();
    directActionsByCard = {};
    var dock = ensureHorrorActionDock();
    dock.hidden = !promptActive;
    if(!promptActive) {
      legacyHorrorMenuOpen = false;
      hideCardActionMenu();
      return;
    }

    var legalActions = parseVariables()['HellbreakLegalActionsP' + viewer];
    if(!Array.isArray(legalActions)) legalActions = [];
    legalActions.forEach(function(action) {
      var id = String(action.id || '').toUpperCase();
      if(id === 'PLAY_CARD' || id === 'ATTACK' || id === 'SCHEME') {
        (Array.isArray(action.cards) ? action.cards : []).forEach(function(mzID) {
          addDirectAction(String(mzID), {
            id: id,
            label: id === 'PLAY_CARD' ? 'Play' : (id === 'ATTACK' ? 'Attack' : 'Scheme')
          });
        });
      } else if(id === 'ABILITY') {
        (Array.isArray(action.abilities) ? action.abilities : []).forEach(function(ability) {
          addDirectAction(String(ability.mzID || ''), {
            id: 'ABILITY',
            label: String(ability.label || 'Use ability'),
            abilityIndex: numberValue(ability.abilityIndex, -1)
          });
        });
      }
    });

    Object.keys(directActionsByCard).forEach(function(mzID) {
      var card = document.getElementById(mzID);
      if(!card) return;
      var actions = directActionsByCard[mzID];
      var label = actions.length === 1 ? actions[0].label : 'Choose action';
      card.classList.add('hb-direct-action-card');
      card.setAttribute('data-hb-action-label', label);
      card.setAttribute('data-hb-original-title', card.getAttribute('title') || '');
      card.setAttribute('title', label + ' ' + (card.querySelector('img') ? 'this card' : ''));
      card.setAttribute('role', 'button');
      card.setAttribute('tabindex', '0');
    });

    var ids = legalActions.map(function(action) { return String(action.id || '').toUpperCase(); });
    var slumber = dock.querySelector('[data-action="SLUMBER"]');
    var pass = dock.querySelector('[data-action="PASS"]');
    if(slumber) slumber.hidden = ids.indexOf('SLUMBER') === -1;
    if(pass) pass.hidden = ids.indexOf('PASS') === -1;
    dock.classList.toggle('is-busy', directActionBusy);
  }

  function installDirectActionEvents() {
    if(window.__hellbreakDirectActionEvents) return;
    window.__hellbreakDirectActionEvents = true;
    document.addEventListener('click', function(event) {
      var card = event.target.closest && event.target.closest('.hb-direct-action-card[data-mzid]');
      if(!card) {
        if(!event.target.closest || !event.target.closest('#hbCardActionMenu')) hideCardActionMenu();
        return;
      }
      var mzID = card.getAttribute('data-mzid') || card.id || '';
      var actions = directActionsByCard[mzID] || [];
      if(actions.length === 0) return;
      event.preventDefault();
      event.stopPropagation();
      if(typeof event.stopImmediatePropagation === 'function') event.stopImmediatePropagation();
      if(actions.length === 1) submitDirectAction(actions[0], mzID);
      else showCardActionMenu(card, actions);
    }, true);
    document.addEventListener('keydown', function(event) {
      if(event.key !== 'Enter' && event.key !== ' ') return;
      var card = event.target.closest && event.target.closest('.hb-direct-action-card[data-mzid]');
      if(!card) return;
      event.preventDefault();
      card.click();
    });
  }

  function updateTable() {
    installCardBack();
    var table = document.getElementById('hellbreakTable');
    if(!table) return;
    var viewer = viewerPlayer();
    var opponent = viewer === 1 ? 2 : 1;
    var round = Math.max(1, numberValue(window.TurnNumberData, 1));
    var phase = String(window.CurrentPhaseData || 'SETUP_LOCATION').toUpperCase();
    var initiative = numberValue(window.InitiativePlayerData, 1);
    var priority = numberValue(window.TurnPlayerData, initiative);
    var tutorial = parseVariables().GameMode === 'tutorial';

    setText('hbRound', 'Round ' + round);
    setText('hbPhase', phaseNames[phase] || phase.replace(/_/g, ' '));
    setText('hbInitiative', 'Initiative: Player ' + initiative);
    setText('hbPriority', 'Priority: Player ' + priority);
    setText('hbMyLabel', viewer === 1 || viewer === 2 ? 'Player ' + viewer : 'Observed Player');
    setText('hbTheirLabel', tutorial ? 'Tutorial Opponent · Auto-Pass' : 'Player ' + opponent);
    ensureRenderedValue('myHealth', window.myHealthData);
    ensureRenderedValue('theirHealth', window.theirHealthData);

    var mySide = document.getElementById('hbMySide');
    var theirSide = document.getElementById('hbTheirSide');
    if(mySide) {
      mySide.classList.toggle('is-priority', priority === viewer);
      mySide.classList.toggle('is-initiative', initiative === viewer);
    }
    if(theirSide) {
      theirSide.classList.toggle('is-priority', priority === opponent);
      theirSide.classList.toggle('is-initiative', initiative === opponent);
    }

    var myHealthStack = document.getElementById('myHealthStackSlot');
    var theirHealthStack = document.getElementById('theirHealthStackSlot');
    if(myHealthStack) myHealthStack.classList.toggle('hb-top-health-vertical', numberValue(window.myTopHealthRemainingData, 0) === 1);
    if(theirHealthStack) theirHealthStack.classList.toggle('hb-top-health-vertical', numberValue(window.theirTopHealthRemainingData, 0) === 1);

    table.setAttribute('data-phase', phase);
    renderHistory();
    updateWinner(viewer);
    updateDirectHorrorActions(viewer, phase, priority);
  }

  function installLogToggle() {
    var button = document.getElementById('hbLogToggle');
    var table = document.getElementById('hellbreakTable');
    if(!button || !table || button.dataset.installed === '1') return;
    button.dataset.installed = '1';
    button.addEventListener('click', function() {
      var collapsed = table.classList.toggle('log-collapsed');
      button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
      button.textContent = collapsed ? 'Show History' : 'History';
    });
  }

  var scheduled = false;
  function scheduleUpdate() {
    if(scheduled) return;
    scheduled = true;
    window.requestAnimationFrame(function() {
      scheduled = false;
      updateTable();
    });
  }

  function install() {
    installCardBack();
    installLogToggle();
    installDirectActionEvents();
    updateTable();
    var table = document.getElementById('hellbreakTable');
    if(table && window.MutationObserver && !window.__hellbreakTableObserver) {
      window.__hellbreakTableObserver = new MutationObserver(scheduleUpdate);
      window.__hellbreakTableObserver.observe(table, { childList: true, subtree: true });
    }
    if(document.body && window.MutationObserver && !window.__hellbreakDecisionObserver) {
      window.__hellbreakDecisionObserver = new MutationObserver(scheduleUpdate);
      window.__hellbreakDecisionObserver.observe(document.body, { childList: true, subtree: true });
    }
    window.setInterval(updateTable, 900);
  }

  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', install);
  else install();
})();
