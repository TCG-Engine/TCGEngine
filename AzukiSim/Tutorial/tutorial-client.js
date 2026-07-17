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
    { title: 'Leader and Garden', body: 'Your Leader begins in the Garden. Entities in the Garden can attack and can usually be attacked while tapped.' },
    { title: 'Starting IKZ', body: 'The first player begins with 1 ready IKZ. The second player receives their first IKZ and a one-use IKZ token when their first turn begins. At the start of later turns, you ready your IKZ and gain another, up to 10.' },
    { title: 'Alley and Gate', body: 'The Alley protects developing entities. Your Gate can tap to portal a ready Alley entity into the Garden. An entity\'s Gate Power can determine the strength of the Gate\'s When Gated ability.' },
    { title: 'This lesson', body: 'You will spend your single starting IKZ, portal an entity with Gate Power 1, and use Surge Gate\'s When Gated ability to recover a cost-1 Weapon from your discard.' }
  ];

  var steps = {
    0: { title: 'Spend your starting IKZ', body: 'Select Black Jade Recruit in your hand, then choose the Alley. Its cost of 1 uses the only ready IKZ you start with.', target: targetPlayRecruit },
    1: { title: 'Gate Black Jade Recruit', body: 'Select Surge Gate to portal Black Jade Recruit into the Garden. Recruit has Gate Power 1.', target: function() { return document.getElementById('myGate-0') || document.getElementById('myGateSlot'); } },
    2: { title: 'Resolve When Gated', body: 'Surge Gate\'s When Gated ability may play a Weapon from your discard whose cost is no greater than the portaled entity\'s Gate Power. Recruit has Gate Power 1, so the cost-1 Lightning Shuriken is eligible.', target: targetDiscardZone, continueAction: true },
    3: { title: 'Choose Lightning Shuriken', body: 'Now select Lightning Shuriken from the card-selection popup.', target: targetPopupShuriken },
    4: { title: 'Equip Raizan', body: 'Attach Lightning Shuriken to your Leader, Raizan. Weapons add their attack and abilities to the equipped entity.', target: targetEquipLeader },
    5: { title: 'Make your first attack', body: 'Lightning Shuriken lets the equipped Raizan attack. Select Raizan, then choose the opposing Leader.', target: targetLeaderAttack },
    6: { title: 'Response window', body: 'Before combat damage resolves, the defending player may play a Response card or pass. Continue to have the scripted opponent pass.', target: null, continueAction: true },
    7: { title: 'When Attacking and self-mill', body: 'Lightning Shuriken\'s When Attacking ability put the top card of your deck into your discard before damage. This is often helpful: discarded cards can fuel gated abilities, recursion, and other discard synergies.', target: null, continueAction: true },
    8: { title: 'End your turn', body: 'Black Jade Recruit entered the Garden this turn, so it still has cooldown. Pass so it can ready for a follow-up attack next turn.', target: function() { return document.querySelector('#myLeaderHealth .widget-button-pass'); } },
    9: { title: 'Opponent turn and IKZ', body: 'Your opponent receives their first IKZ and one-use IKZ token at the start of this scripted turn, then passes. Your next turn will ready your cards and give you a second IKZ.', target: null, continueAction: true },
    10: { title: 'Make a follow-up attack', body: 'Black Jade Recruit is now ready. Select it, then choose the opposing Leader for your second attack of the lesson.', target: targetRecruitAttack },
    11: { title: 'Another response window', body: 'Every attack gives the defending player this chance to respond before combat damage. Continue to have the opponent pass.', target: null, continueAction: true },
    12: { title: 'Follow-up damage', body: 'Black Jade Recruit dealt its damage and became tapped. This was your second attack; the first came from Raizan with Lightning Shuriken.', target: null, continueAction: true },
    13: { title: 'Lesson complete', body: 'You spent IKZ, used Gate Power, equipped a Weapon, triggered a When Attacking ability, and made two attacks. Now it\'s your turn to play a full game against the bot or another player!', target: null, complete: true }
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

  function targetPopupShuriken() {
    var popup = document.getElementById('mzchoose-popup');
    if(!popup) return null;
    var image = popup.querySelector('img');
    var node = image;
    while(node && node !== popup) {
      if(typeof node.onclick === 'function') return node;
      node = node.parentElement;
    }
    return image;
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
    if(!introDone) { renderIntro(); return; }
    ensureUI();
    var number = tutorialStep();
    var step = steps[number] || steps[13];
    currentTarget = step.target ? step.target() : null;
    if(lastStepContent !== number) {
      lastStepContent = number;
      panel.innerHTML = '<div class="azuki-tutorial-kicker">Basics &middot; Step ' + (Math.min(number, 13) + 1) + ' of 14</div>' +
        '<h2>' + step.title + '</h2><p>' + step.body + '</p>' + progressHTML(Math.min(number, 13), 14) +
        (step.continueAction ? '<div class="azuki-tutorial-actions"><button type="button" class="azuki-tutorial-button">Continue</button></div>' : '') +
        (step.complete ? '<div class="azuki-tutorial-actions"><button type="button" class="azuki-tutorial-button">Return to menu</button></div>' : '');
      if(step.continueAction) {
        panel.querySelector('button').addEventListener('click', function(event) {
          event.currentTarget.disabled = true;
          event.currentTarget.textContent = 'Continuing...';
          SubmitInput('10001', '&cardID=' + encodeURIComponent('Tutorial!CustomInput!Continue'));
        });
      }
      if(step.complete) {
        panel.querySelector('button').addEventListener('click', function() {
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
