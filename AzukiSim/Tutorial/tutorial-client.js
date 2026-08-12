(function() {
  'use strict';

  var introIndex = 0;
  var introDone = false;
  var cutout = null;
  var panel = null;
  var currentTarget = null;
  var updateQueued = false;
  var lastStepContent = null;

  var intro = [
    { title: 'Welcome to Azuki TCG', body: 'This short guided match teaches the core turn loop of Azuki TCG.' },
    { title: 'Opening hand', body: 'Each player draws 7 cards before the game. You may take one mulligan: put your entire hand on the bottom of your deck, draw 7 new cards, then shuffle the cards you put back into your deck.' },
    { title: 'Drawing cards', body: 'At the start of each turn, the active player draws 1 card. The player who goes first skips that draw on the first turn; the second player draws normally on their first turn.' },
    { title: 'Leader and Garden', body: 'Your Leader begins in the Garden. A Leader has no attack power on its own and cannot attack until a Weapon or another effect gives it attack power.' },
    { title: 'Choosing attack targets', body: 'Leaders can be attacked while they are standing. Other entities in the Garden normally cannot be chosen as attack targets until they are tapped.' },
    { title: 'Starting IKZ', body: 'The first player begins with 1 ready IKZ. The second player receives their first IKZ and a one-use IKZ token when their first turn begins. At the start of later turns, you ready your IKZ and gain another, up to 10.' },
    { title: 'Alley and Gate', body: 'The Alley protects developing entities. Your Gate can tap to portal a ready Alley entity into the Garden. An entity\'s Gate Power can determine the strength of the Gate\'s When Gated ability.' },
    { title: 'This lesson', body: 'You will use Black Jade Recruit to discard Lightning Shuriken, portal Recruit with Gate Power 1, and use Surge Gate to recover and equip that Weapon.' }
  ];

  var steps = {
    0: { title: 'Spend your starting IKZ', body: 'Select Black Jade Recruit in your hand, then choose the Alley. Its cost of 1 uses the only ready IKZ you start with.', target: targetPlayRecruit },
    1: { title: 'Discard Lightning Shuriken', body: 'Black Jade Recruit may discard a Weapon when played. Choose Lightning Shuriken from your hand; Surge Gate will recover it shortly.', target: targetPopupChoice },
    2: { title: 'Search the top five', body: 'Recruit now looks at the top five cards of your deck. Choose Black Jade Dagger to reveal it and add it to your hand.', target: targetPopupChoice },
    3: { title: 'Bottom the remaining cards', body: 'Put the other four revealed cards on the bottom of your deck in any order, then confirm. This completes Recruit\'s On Play ability.', target: targetRearrangePopup },
    4: { title: 'Gate Black Jade Recruit', body: 'Select Surge Gate to portal Black Jade Recruit into the Garden. Recruit has Gate Power 1.', target: function() { return document.getElementById('myGate-0') || document.getElementById('myGateSlot'); } },
    5: { title: 'Resolve When Gated', body: 'Surge Gate may play a Weapon from your discard whose cost is no greater than the portaled entity\'s Gate Power. Recruit has Gate Power 1, so the cost-1 Lightning Shuriken is eligible.', target: targetDiscardZone, continueAction: true },
    6: { title: 'Recover Lightning Shuriken', body: 'Choose the Lightning Shuriken that Recruit discarded earlier.', target: targetPopupChoice },
    7: { title: 'Equip Raizan', body: 'Attach Lightning Shuriken to your Leader, Raizan. Leaders have no attack power by themselves; this Weapon gives Raizan attack power and makes the attack legal.', target: targetEquipLeader },
    8: { title: 'Make your first attack', body: 'Select the now-armed Raizan, then choose the opposing Leader. A non-Leader entity would normally need to be tapped before you could choose it instead.', target: targetLeaderAttack },
    9: { title: 'First response window', body: 'The defender may play a [Response] card or pass, but the second player has not received any IKZ yet and cannot pay for Lightning Orb. Continue to have them pass.', target: targetOpponentHand, continueAction: true },
    10: { title: 'No IKZ, no Response', body: 'Because the opponent could not pay for Lightning Orb, Raizan\'s attack resolved normally. Having a Response card in hand is not enough—you must still pay its IKZ cost.', target: null, continueAction: true },
    11: { title: 'End your turn', body: 'Black Jade Recruit entered the Garden this turn, so it still has cooldown. Pass so it can ready for a follow-up attack next turn.', target: function() { return document.querySelector('#myLeaderHealth .widget-button-pass'); } },
    12: { title: 'Opponent turn and IKZ', body: 'Your opponent receives their first IKZ and one-use IKZ token, then passes while keeping that IKZ ready. Your next turn will ready your cards, draw a card, and give you a second IKZ.', target: null, continueAction: true },
    13: { title: 'Make a follow-up attack', body: 'Black Jade Recruit is now ready. Select it, then choose the opposing Leader for your second attack of the lesson.', target: targetRecruitAttack },
    14: { title: 'A funded Response', body: 'This time the opponent has 1 ready IKZ. Continue to have them pay it and cast Lightning Orb on the attacking Black Jade Recruit.', target: targetOpponentHand, continueAction: true },
    15: { title: 'Attack stopped', body: 'Lightning Orb dealt 1 damage to the 1-health Recruit and defeated it before combat damage. The attack ended without damaging the opposing Leader.', target: targetOpponentDiscard, continueAction: true },
    16: { title: 'Lesson complete', body: 'You covered opening draws and mulligans, IKZ, card searching, Gate Power, Weapons, legal attack targets, and Responses. Continue this full Raizan starter match against the bot with the tutorial rails removed.', target: null, complete: true }
  };

  function parseVars() {
    try {
      var parsed = JSON.parse(window.DecisionQueueVariablesData || '{}');
      return parsed && typeof parsed === 'object' ? parsed : {};
    } catch (e) { return {}; }
  }

  function tutorialStep() {
    var value = parseInt(parseVars().TutorialStep || '0', 10);
    return Number.isFinite(value) ? value : 0;
  }

  function targetPlayRecruit() {
    if(window.SelectionMode && window.SelectionMode.active) {
      return document.getElementById('myAlleySlot') || document.getElementById('myAlley');
    }
    return document.getElementById('myHand-0') || document.getElementById('myHandSlot');
  }

  function targetDiscardZone() {
    return document.getElementById('myDiscard-0') || document.getElementById('myDiscardSlot');
  }

  function targetOpponentHand() {
    return document.getElementById('theirHand-0') || document.getElementById('theirHandSlot');
  }

  function targetOpponentDiscard() {
    return document.getElementById('theirDiscard-0') || document.getElementById('theirDiscardSlot');
  }

  function targetPopupChoice() {
    var popup = document.getElementById('mzchoose-popup');
    if(!popup) {
      popup = document.getElementById('mzrearrange-popup');
      if(!popup) return null;
      return popup.querySelector('.mzrearrange-card.selectable:not(.selected)') ||
        popup.querySelector('.mzrearrange-card.selectable');
    }
    var image = popup.querySelector('img');
    var node = image;
    while(node && node !== popup) {
      if(typeof node.onclick === 'function') return node;
      node = node.parentElement;
    }
    return image;
  }

  function targetRearrangePopup() {
    var popup = document.getElementById('mzrearrange-popup');
    return popup ? (popup.querySelector('.mzrearrange-btn-submit') || popup.querySelector('.mzrearrange-modal') || popup) : null;
  }

  function targetEquipLeader() {
    return document.getElementById('myGarden-0') || document.getElementById('myGardenSlot');
  }

  function targetLeaderAttack() {
    if(window.SelectionMode && window.SelectionMode.active) {
      return document.getElementById('theirGarden-0') || document.getElementById('theirGardenSlot');
    }
    return document.getElementById('myGarden-0') || document.getElementById('myGardenSlot');
  }

  function targetRecruitAttack() {
    if(window.SelectionMode && window.SelectionMode.active) {
      return document.getElementById('theirGarden-0') || document.getElementById('theirGardenSlot');
    }
    return document.getElementById('myGarden-1') || document.querySelector('#myGarden [id]') || document.getElementById('myGardenSlot');
  }

  function ensureUI() {
    if(!cutout) {
      cutout = document.createElement('div');
      cutout.className = 'azuki-tutorial-cutout';
      document.body.appendChild(cutout);
    }
    if(!panel) {
      panel = document.createElement('section');
      panel.className = 'azuki-tutorial-panel';
      panel.setAttribute('role', 'dialog');
      panel.setAttribute('aria-live', 'polite');
      document.body.appendChild(panel);
    }
  }

  function progressHTML(active, count) {
    var html = '<div class="azuki-tutorial-progress" aria-hidden="true">';
    for(var i = 0; i < count; ++i) html += '<span class="' + (i <= active ? 'is-done' : '') + '"></span>';
    return html + '</div>';
  }

  function renderIntro() {
    ensureUI();
    currentTarget = null;
    cutout.style.display = 'none';
    panel.className = 'azuki-tutorial-panel is-centered';
    var slide = intro[introIndex];
    panel.innerHTML = '<div class="azuki-tutorial-kicker">Learn to Play</div>' +
      '<h2>' + slide.title + '</h2><p>' + slide.body + '</p>' + progressHTML(introIndex, intro.length) +
      '<div class="azuki-tutorial-actions"><button type="button" class="azuki-tutorial-button">' +
      (introIndex === intro.length - 1 ? 'Start lesson' : 'Next') + '</button></div>';
    panel.querySelector('button').addEventListener('click', function() {
      if(introIndex < intro.length - 1) { introIndex++; renderIntro(); }
      else { introDone = true; renderStep(); }
    });
  }

  function placePanel(rect) {
    panel.className = 'azuki-tutorial-panel';
    var panelWidth = Math.min(390, window.innerWidth - 28);
    var left = Math.max(14, Math.min(window.innerWidth - panelWidth - 14, rect.left));
    var below = rect.bottom + 18;
    var top = below + 210 < window.innerHeight ? below : Math.max(14, rect.top - 230);
    panel.style.left = left + 'px';
    panel.style.top = top + 'px';
    panel.style.transform = 'none';
  }

  function renderStep() {
    if(parseVars().GameMode !== 'tutorial') {
      if(cutout) cutout.style.display = 'none';
      if(panel) panel.style.display = 'none';
      return;
    }
    if(!introDone) { renderIntro(); return; }
    ensureUI();
    var number = tutorialStep();
    // The modern searcher combines card selection and bottom-deck ordering into one popup and
    // sends only one decision when Confirm is clicked. Preserve the lesson's intermediate
    // instruction locally once the required dagger has been selected.
    if(number === 2 && document.querySelector('#mzrearrange-popup .mzrearrange-card.selectable.selected')) {
      number = 3;
    }
    var step = steps[number] || steps[16];
    currentTarget = step.target ? step.target() : null;
    if(lastStepContent !== number) {
      lastStepContent = number;
      panel.innerHTML = '<div class="azuki-tutorial-kicker">Basics &middot; Step ' + (Math.min(number, 16) + 1) + ' of 17</div>' +
        '<h2>' + step.title + '</h2><p>' + step.body + '</p>' + progressHTML(Math.min(number, 16), 17) +
        (step.continueAction ? '<div class="azuki-tutorial-actions"><button type="button" class="azuki-tutorial-button">Continue</button></div>' : '') +
        (step.complete ? '<div class="azuki-tutorial-actions"><button type="button" class="azuki-tutorial-button" data-action="bot">Continue vs bot</button><button type="button" class="azuki-tutorial-button" data-action="menu">Return to menu</button></div>' : '');
      if(step.continueAction) {
        panel.querySelector('button').addEventListener('click', function(event) {
          event.currentTarget.disabled = true;
          event.currentTarget.textContent = 'Continuing...';
          SubmitInput('10001', '&cardID=' + encodeURIComponent('Tutorial!CustomInput!Continue'));
        });
      }
      if(step.complete) {
        panel.querySelector('[data-action="bot"]').addEventListener('click', function(event) {
          event.currentTarget.disabled = true;
          event.currentTarget.textContent = 'Starting...';
          SubmitInput('10001', '&cardID=' + encodeURIComponent('Tutorial!CustomInput!Continue'));
        });
        panel.querySelector('[data-action="menu"]').addEventListener('click', function() {
          window.location.href = '/TCGEngine/SharedUI/Sites/AzukiSim/MainMenu.php';
        });
      }
    }

    if(currentTarget) {
      var rect = currentTarget.getBoundingClientRect();
      var pad = 8;
      cutout.style.display = '';
      cutout.style.left = Math.max(2, rect.left - pad) + 'px';
      cutout.style.top = Math.max(2, rect.top - pad) + 'px';
      cutout.style.width = Math.max(24, rect.width + pad * 2) + 'px';
      cutout.style.height = Math.max(24, rect.height + pad * 2) + 'px';
      placePanel(rect);
      if(rect.bottom < 0 || rect.top > window.innerHeight) currentTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
      cutout.style.display = 'none';
      panel.className = 'azuki-tutorial-panel is-centered';
      panel.style.left = '';
      panel.style.top = '';
      panel.style.transform = '';
    }
  }

  function scheduleRender() {
    if(updateQueued) return;
    updateQueued = true;
    window.requestAnimationFrame(function() {
      updateQueued = false;
      renderStep();
    });
  }

  // Do not place a client-side event shield over the board. The server-side tutorial validator
  // authoritatively rejects off-script actions, while leaving the highlighted control's complete
  // hit area (including game-owned wrappers and pseudo-elements) available to mouse and touch.

  var boardObserver = new MutationObserver(scheduleRender);
  ['myStuff', 'theirStuff', 'globalStuff'].forEach(function(id) {
    var el = document.getElementById(id);
    if(el) boardObserver.observe(el, { childList: true, subtree: true, attributes: true, attributeFilter: ['class'] });
  });
  window.addEventListener('resize', scheduleRender);
  window.setInterval(scheduleRender, 300);

  // A resumed tutorial should return directly to its current gameplay step.
  introDone = tutorialStep() > 0;
  renderStep();
})();
