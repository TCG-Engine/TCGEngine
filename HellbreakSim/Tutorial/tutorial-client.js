(function() {
  'use strict';

  var introIndex = 0;
  var introDone = false;
  var focus = null;
  var panel = null;
  var lastSignature = '';
  var dismissed = false;

  var intro = [
    { title: 'Welcome to Hellbreak', body: 'This guided Dracula-versus-Jaws scenario teaches the quick-start flow across several rounds. The tutorial opponent automatically handles setup and passes its actions.' },
    { title: 'The three phases', body: 'Every round is Feeding, Horror, then Refresh. Feeding builds resources and decides initiative. Horror is where you play cards, attack, and scheme. Refresh prepares the next round.' },
    { title: 'Your Vault', body: 'Your monster and every card tucked beneath it form your Vault. Their resource bars generate blood, malice, draws, and loyalty icons at the start of every round.' },
    { title: 'Initiative bids', body: 'You may tuck one hand card into your Vault. Higher printed blood cost wins the bid, and the winner chooses who acts first. A bid improves future resources but removes that card from your hand.' },
    { title: 'Health cards', body: 'Eight face-down cards represent sixteen monster Health. Each takes two damage. When a card is revealed, you may use its Jumpscare before it would be discarded.' },
    { title: 'Your first rounds', body: 'Choose a location, keep the authored hand, bid for initiative, play and attack with a minion, then repeatedly scheme at North Beach until you take control of it.' }
  ];

  function vars() {
    try {
      var parsed = JSON.parse(window.DecisionQueueVariablesData || '{}');
      return parsed && typeof parsed === 'object' ? parsed : {};
    } catch(error) { return {}; }
  }

  function number(value, fallback) {
    var parsed = parseInt(value, 10);
    return Number.isFinite(parsed) ? parsed : fallback;
  }

  function visible(element) {
    if(!element) return false;
    var style = window.getComputedStyle(element);
    return style.display !== 'none' && style.visibility !== 'hidden' && element.getClientRects().length > 0;
  }

  function decisionTarget() {
    var selectors = [
      '#mzchoose-popup', '#mzmodal-popup', '#mzmultichoose-popup', '#mzrearrange-popup',
      '#yesno-decision-modal',
      '.mzchoose-popup', '.mzmodal-popup', '.mzmultichoose-popup', '.mzmodal-panel',
      '.mzmultichoose-panel', '.mzchoose-modal', '.yesno-decision-panel', '.modal-dialog'
    ];
    for(var i = 0; i < selectors.length; ++i) {
      var matches = document.querySelectorAll(selectors[i]);
      for(var j = 0; j < matches.length; ++j) if(visible(matches[j])) return matches[j];
    }
    return null;
  }

  function pendingDecision() {
    try {
      var queue = typeof window.ParseDecisionQueue === 'function'
        ? window.ParseDecisionQueue(window.myDecisionQueueData || '')
        : [];
      if(!Array.isArray(queue)) return null;
      for(var i = 0; i < queue.length; ++i) {
        if(queue[i] && !queue[i].removed) return queue[i];
      }
    } catch(error) {}
    return null;
  }

  function decisionPrompt(decision) {
    return String((decision && (decision.Tooltip || decision.tooltip)) || '')
      .replace(/_/g, ' ').trim().toLowerCase();
  }

  function parseLog() {
    var data = vars().HellbreakPublicLog;
    return Array.isArray(data) ? data : [];
  }

  function roundLogTypes(round) {
    return parseLog().filter(function(entry) { return number(entry.round, 0) === round; })
      .map(function(entry) { return String(entry.type || '').toUpperCase(); });
  }

  function hasLogType(type) {
    type = String(type || '').toUpperCase();
    return parseLog().some(function(entry) { return String(entry.type || '').toUpperCase() === type; });
  }

  function locationTarget(slot) {
    var card = document.getElementById('Locations-' + slot);
    if(card) return card;
    var root = document.getElementById('LocationsSlot') || document.getElementById('Locations');
    if(!root) return null;
    return root;
  }

  function progress(active, count) {
    var html = '<div class="hb-tutorial-progress" aria-hidden="true">';
    for(var i = 0; i < count; ++i) html += '<span class="' + (i <= active ? 'is-done' : '') + '"></span>';
    return html + '</div>';
  }

  function ensureUI() {
    if(!focus) {
      focus = document.createElement('div');
      focus.className = 'hb-tutorial-focus';
      document.body.appendChild(focus);
    }
    if(!panel) {
      panel = document.createElement('section');
      panel.className = 'hb-tutorial-panel';
      panel.setAttribute('role', 'dialog');
      panel.setAttribute('aria-live', 'polite');
      document.body.appendChild(panel);
    }
  }

  function renderIntro() {
    ensureUI();
    focus.style.display = 'none';
    panel.style.display = '';
    panel.className = 'hb-tutorial-panel is-centered';
    panel.style.left = panel.style.top = panel.style.transform = '';
    var slide = intro[introIndex];
    panel.innerHTML = '<div class="hb-tutorial-kicker">Learn to Play</div><h2>' + slide.title + '</h2><p>' + slide.body + '</p>' +
      progress(introIndex, intro.length) + '<div class="hb-tutorial-actions"><button class="hb-tutorial-button" type="button">' +
      (introIndex === intro.length - 1 ? 'Begin lesson' : 'Next') + '</button></div>';
    panel.querySelector('button').addEventListener('click', function() {
      if(introIndex < intro.length - 1) { ++introIndex; renderIntro(); return; }
      introDone = true;
      if(typeof window.SubmitInput === 'function') {
        window.SubmitInput('10001', '&cardID=' + encodeURIComponent('Tutorial!CustomInput!Continue'));
      }
      renderLesson();
    });
  }

  function lesson() {
    var phase = String(window.CurrentPhaseData || 'SETUP_LOCATION').toUpperCase();
    var round = Math.max(0, number(window.TurnNumberData, 0));
    var target = decisionTarget();
    var prompt = decisionPrompt(pendingDecision());
    var base = { target: target, index: 0, count: 12 };

    if(hasLogType('LOCATION')) {
      if(String(vars().TutorialLocationControlExplained || '0') !== '1') return Object.assign(base, {
        title: 'You took control of North Beach',
        body: 'Reaching its malice value immediately clears both players\' malice rows, turns the location to face you, awards its resource icons, and resolves any Take Control ability.',
        target: locationTarget(1), index: 10, acknowledge: 'ACK_LOCATION_CONTROL'
      });
      if(String(vars().TutorialRetakeExplained || '0') !== '1') return Object.assign(base, {
        title: 'Taking control again',
        body: 'Control does not stop you from building malice here. If your malice row reaches the value while you already control the location, clear both rows and perform the entire takeover again: turn it toward you, collect its resources, and use its Take Control ability.',
        target: locationTarget(1), index: 11, acknowledge: 'ACK_RETAKE_CONTROL'
      });
      return Object.assign(base, {
        title: 'Quick Start complete', body: 'You played, attacked, schemed, and took control of a location through normal play. Continue practicing against the passive opponent or return to the menu.', target: null, index: 11, complete: true
      });
    }
    if(phase === 'SETUP_LOCATION') return Object.assign(base, {
      title: 'Choose your location', body: 'Choose Carfax Abbey. Locations are contested spaces where minions fight and scheme. Your unused location leaves this game.', index: 0
    });
    if(phase === 'SETUP_MULLIGAN') return Object.assign(base, {
      title: 'Keep the authored hand', body: 'Select no cards and confirm. A normal mulligan may put any number on the bottom, then draws the same number.', index: 1
    });
    if(phase === 'FEED_COLLECT') return Object.assign(base, {
      title: round <= 1 ? 'Collect from your Vault' : 'Begin another round', body: round <= 1
        ? 'This step is automatic. The lesson gives you 2 blood, 1 malice, 2 draws, and one Cursed loyalty icon. Malice pays for frightening effects and special costs.'
        : 'Feeding happens again. Your Vault generates resources and you draw more cards; malice already placed at locations remains there between rounds.', target: document.getElementById('myVaultSlot') || document.getElementById('myMonsterSlot'), index: round <= 1 ? 2 : 9
    });
    if(phase === 'FEED_BID') return Object.assign(base, {
      title: round <= 1 ? 'Bid for initiative' : 'Confirm no bid', body: round <= 1
        ? 'Bid Mina Seward if she is available. Both bid cards enter their Vaults; the higher printed blood cost wins. The tutorial opponent declines.'
        : 'Select no card and confirm. You retain initiative against this passive opponent and keep your hand for normal play.', index: round <= 1 ? 3 : 9
    });
    if(phase === 'FEED_RESOLVE') return Object.assign(base, {
      title: 'Take initiative', body: 'If you won, choose Take Initiative. Initiative determines who takes the first Horror action and acts first during ordered choices.', index: 3
    });
    if(phase === 'HORROR') {
      var types = roundLogTypes(round);
      if(prompt === 'choose a card to play') return Object.assign(base, {
        title: 'Choose Transylvanian Wolf', body: 'Click Transylvanian Wolf directly in your hand. Cards you can legally play are highlighted on the board.', target: document.getElementById('myHandSlot'), index: 4
      });
      if(prompt === 'choose a location for the minion') return Object.assign(base, {
        title: 'Place the Wolf', body: 'Click Carfax Abbey directly on the battlefield. The highlighted location cards are the legal destinations for your minion.', target: document.getElementById('LocationsSlot'), index: 5
      });
      if(prompt === 'pay 1 malice to ready this minion') return Object.assign(base, {
        title: 'Ready the Wolf', body: 'Choose Yes to pay 1 malice and ready Transylvanian Wolf. Minions normally enter exhausted; a ready minion can attack when priority returns.', target: target, index: 6
      });
      if(prompt === 'choose a character to attack') return Object.assign(base, {
        title: 'Attack with the Wolf', body: 'Click the highlighted Transylvanian Wolf on your board to declare it as the attacker.', target: document.getElementById('myCharactersSlot'), index: 7
      });
      if(prompt === 'choose an enemy character to attack') return Object.assign(base, {
        title: 'Choose the attack target', body: 'Click the highlighted Jaws directly on the opponent\'s board. Combat damage is dealt simultaneously, and the attacker exhausts.', target: document.getElementById('theirMonsterSlot') || document.getElementById('hbTheirSide'), index: 7
      });
      if(prompt === 'choose the monsters attack location') return Object.assign(base, {
        title: 'Choose an attack location', body: 'Click the highlighted location on the battlefield to say where the monster is attacking.', target: document.getElementById('LocationsSlot'), index: 7
      });
      if(prompt === 'choose a character to scheme') return Object.assign(base, {
        title: 'Scheme with Dracula', body: 'Click the highlighted lurking Dracula on your board to begin scheming.', target: document.getElementById('myMonsterSlot'), index: 8
      });
      if(prompt === 'choose the monsters scheme location') return Object.assign(base, {
        title: 'Scheme at North Beach', body: 'Click North Beach directly on the battlefield. Dracula\'s Haunt adds 1 malice there; reaching its value of 3 immediately takes control.', target: locationTarget(1), index: round <= 1 ? 8 : 9
      });
      if(prompt === 'arrange foreseen cards on top or bottom') return Object.assign(base, {
        title: 'Resolve Foresee', body: 'Arrange the revealed cards between Top and Bottom, then confirm. Cards on Top are drawn first; cards on Bottom go beneath your deck.', target: target, index: 8
      });
      if(round <= 1 && types.indexOf('PLAY_CARD') === -1) return Object.assign(base, {
        title: 'Play Transylvanian Wolf', body: 'Click the highlighted Transylvanian Wolf directly in your hand, then place it at Carfax Abbey. When prompted, pay 1 malice to ready it; minions normally enter exhausted.', target: target || document.getElementById('myHandSlot'), index: 4
      });
      if(round <= 1 && types.indexOf('ATTACK') === -1) return Object.assign(base, {
        title: 'Attack with the Wolf', body: 'When priority returns, click the highlighted Transylvanian Wolf to attack, then click the highlighted Jaws directly on the board. Attackers exhaust; combat damage is dealt simultaneously.', target: target || document.getElementById('myCharactersSlot'), index: 7
      });
      if(types.indexOf('SCHEME') === -1) return Object.assign(base, {
        title: round <= 1 ? 'Scheme with Dracula' : 'Build toward control', body: round <= 1
          ? 'When priority returns, click lurking Dracula and choose Scheme, then click North Beach. Foresee rearranges your deck; Haunt adds 1 malice there. Scheming exhausts Dracula.'
          : 'Click lurking Dracula, choose Scheme, then click North Beach. Its malice persists between rounds, so each Haunt moves you closer to its value of 3.', target: target || document.getElementById('myMonsterSlot'), index: round <= 1 ? 8 : 9
      });
      return Object.assign(base, {
        title: 'Enter Slumber', body: round <= 1
          ? 'Choose Slumber from the action bar. You gain 1 malice and take no more Horror actions this round. Because the opponent passed immediately before it, Horror ends.'
          : 'You have added another malice to North Beach. Enter Slumber so Refresh can ready Dracula for another round and another Scheme.', target: document.getElementById('hbHorrorActionDock') || target, index: 9
      });
    }
    if(phase === 'REFRESH_READY' || phase === 'REFRESH_FLIP' || phase === 'REFRESH_HAND') return Object.assign(base, {
      title: 'Refresh', body: round <= 1
        ? 'Cards ready automatically, then you may flip Dracula between lurking and unleashed. Finally, discard down to six cards. Keep Dracula lurking so it can scheme again.'
        : 'Refresh readies Dracula. Keep it lurking and complete any hand-size prompt, then the next Feeding phase begins.', index: 9
    });
    return Object.assign(base, { title: 'Continue the lesson', body: 'Follow the current game prompt.', index: 0 });
  }

  function place(target) {
    if(!target || !visible(target)) {
      focus.style.display = 'none';
      panel.className = 'hb-tutorial-panel is-centered';
      panel.style.left = panel.style.top = panel.style.transform = '';
      return;
    }
    var rect = target.getBoundingClientRect();
    var pad = 8;
    focus.style.display = '';
    focus.style.left = Math.max(2, rect.left - pad) + 'px';
    focus.style.top = Math.max(2, rect.top - pad) + 'px';
    focus.style.width = Math.max(30, rect.width + pad * 2) + 'px';
    focus.style.height = Math.max(30, rect.height + pad * 2) + 'px';
    panel.className = 'hb-tutorial-panel';
    panel.style.transform = 'none';
    var width = Math.min(390, window.innerWidth - 28);
    if(rect.right + width + 18 <= window.innerWidth) {
      panel.style.left = (rect.right + 16) + 'px';
      panel.style.top = Math.max(14, Math.min(window.innerHeight - 250, rect.top)) + 'px';
    } else if(rect.left - width - 18 >= 0) {
      panel.style.left = (rect.left - width - 16) + 'px';
      panel.style.top = Math.max(14, Math.min(window.innerHeight - 250, rect.top)) + 'px';
    } else {
      panel.style.left = Math.max(14, Math.min(window.innerWidth - width - 14, rect.left)) + 'px';
      panel.style.top = (rect.bottom + 235 < window.innerHeight ? rect.bottom + 16 : Math.max(14, rect.top - 225)) + 'px';
    }
  }

  function renderLesson() {
    if(dismissed || vars().GameMode !== 'tutorial') return;
    ensureUI();
    var step = lesson();
    var signature = [step.title, step.body, step.complete ? 1 : 0, step.acknowledge || ''].join('|');
    panel.style.display = '';
    if(signature !== lastSignature) {
      lastSignature = signature;
      panel.innerHTML = '<div class="hb-tutorial-kicker">Quick Start Lesson</div><h2>' + step.title + '</h2><p>' + step.body + '</p>' +
        progress(step.index, step.count) + (step.acknowledge
          ? '<div class="hb-tutorial-actions"><button class="hb-tutorial-button" data-action="acknowledge" type="button">Continue</button></div>'
          : (step.complete ? '<div class="hb-tutorial-actions"><button class="hb-tutorial-button" data-action="practice" type="button">Continue practicing</button><button class="hb-tutorial-button secondary" data-action="menu" type="button">Return to menu</button></div>' : ''));
      if(step.acknowledge) {
        panel.querySelector('[data-action="acknowledge"]').addEventListener('click', function() {
          if(typeof window.SubmitInput === 'function') {
            window.SubmitInput('10001', '&cardID=' + encodeURIComponent('Tutorial!CustomInput!' + step.acknowledge));
          }
        });
      }
      if(step.complete) {
        panel.querySelector('[data-action="practice"]').addEventListener('click', function() { dismissed = true; panel.style.display = 'none'; focus.style.display = 'none'; });
        panel.querySelector('[data-action="menu"]').addEventListener('click', function() { location.href = '/TCGEngine/SharedUI/Sites/HellbreakSim/MainMenu.php'; });
      }
    }
    place(step.target);
    var opponent = document.getElementById('hbTheirLabel');
    if(opponent) opponent.textContent = 'Tutorial Opponent · Auto-Pass';
  }

  function render() {
    if(vars().GameMode !== 'tutorial' || dismissed) return;
    if(!introDone && String(vars().TutorialIntroSeen || '0') !== '1') renderIntro();
    else { introDone = true; renderLesson(); }
  }

  window.addEventListener('resize', render);
  window.setInterval(render, 350);
  render();
})();
