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

  function parseLog() {
    var raw = window.DecisionQueueVariablesData;
    if(raw == null || raw === '') return [];
    try {
      var parsed = JSON.parse(String(raw));
      return parsed && Array.isArray(parsed.HellbreakPublicLog) ? parsed.HellbreakPublicLog : [];
    } catch(error) {
      return [];
    }
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

    setText('hbRound', 'Round ' + round);
    setText('hbPhase', phaseNames[phase] || phase.replace(/_/g, ' '));
    setText('hbInitiative', 'Initiative: Player ' + initiative);
    setText('hbPriority', 'Priority: Player ' + priority);
    setText('hbMyLabel', viewer === 1 || viewer === 2 ? 'Player ' + viewer : 'Observed Player');
    setText('hbTheirLabel', 'Player ' + opponent);
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
    updateTable();
    var table = document.getElementById('hellbreakTable');
    if(table && window.MutationObserver && !window.__hellbreakTableObserver) {
      window.__hellbreakTableObserver = new MutationObserver(scheduleUpdate);
      window.__hellbreakTableObserver.observe(table, { childList: true, subtree: true });
    }
    window.setInterval(updateTable, 900);
  }

  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', install);
  else install();
})();
