// SWUSim Limited room panel — drawn by SharedUI/Render/WaitingRoom.php into #lt-root when the poll carries `limited`
// (SWUSim/Limited/LimitedRoom.php SWULimitedPollView). Spec: docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-
// design.md §3.1, §3.4, §3.6; mockup docs/superpowers/specs/2026-11-04-limited-mockups/lobby-setup-v2.html.
//
//   window.SWULimitedRenderRoom(root, d, ctx)
//     d    the poll's `limited` view
//     ctx  { appBase, lobbyID, post(action, params, cb) → SWUSim/LimitedAction.php, join(), sendToGame(live), backToGame(live) }
//
// The panel redraws only when what it shows changes (the poll runs every 1.5 s); the build countdown ticks in place.
// Every class is lt- prefixed: the shared sheets already own .seat, .card, .tabs, .grid and friends.
(function () {
  'use strict';

  var PACK_MODES = [['single', 'Single Set'], ['hostchoice', 'Host’s Choice'], ['chaos', 'Chaos']];
  var lastSig = '';
  var deadline = 0;          // local ms the build ends (from buildEndsIn)
  var clockTimer = null;
  var draft = null;          // the host's settings as edited, between a change and the poll that confirms it
  var sendTimer = null;
  var lastTick = 0;
  var seq = 0;

  function esc(t) { return String(t == null ? '' : t).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
  function mmss(s) { s = Math.max(0, Math.floor(s)); return Math.floor(s / 60) + ':' + ('0' + (s % 60)).slice(-2); }
  function copy(o) { return JSON.parse(JSON.stringify(o)); }

  function seg(name, options, value, disabled) {
    var id = 'lt-' + name + '-' + (++seq);
    return '<div class="seg lt-seg" role="radiogroup">' + options.map(function (o, i) {
      var oid = id + '-' + i;
      return '<input class="seg__in u-vh" type="radio" name="' + id + '" id="' + oid + '" data-lt="' + name + '" value="' + esc(o[0]) + '"' +
        (String(o[0]) === String(value) ? ' checked' : '') + (disabled ? ' disabled' : '') + '>' +
        '<label class="seg__opt ch" for="' + oid + '">' + esc(o[1]) + '</label>';
    }).join('') + '</div>';
  }
  function select(name, options, value, disabled, extra) {
    return '<span class="selwrap ch lt-sel"><select class="select" data-lt="' + name + '"' + (extra || '') + (disabled ? ' disabled' : '') + '>' +
      options.map(function (o) { return '<option value="' + esc(o) + '"' + (String(o) === String(value) ? ' selected' : '') + '>' + esc(o) + '</option>'; }).join('') +
      '</select></span>';
  }
  function button(cls, label, attrs) { return '<button type="button" class="btn swu2-btn ch ' + cls + '"' + (attrs || '') + '>' + esc(label) + '</button>'; }

  function seatChips(s, isHost) {
    var c = [];
    if (isHost) c.push('<span class="lt-chip">Host</span>');
    if (s.dropped) c.push('<span class="lt-chip lt-chip--off">Left</span>');
    else {
      if (s.ready) c.push('<span class="lt-chip lt-chip--on">Ready</span>');
      else if (s.submitted) c.push('<span class="lt-chip">Submitted</span>');
      if (!s.connected) c.push('<span class="lt-chip lt-chip--off">Disconnected</span>');
    }
    return c.join('');
  }

  function renderSettings(d, editable) {
    var s = (editable && draft) ? draft : d.settings;
    var range = [];
    for (var n = d.packRange[0]; n <= d.packRange[1]; n++) range.push(n);
    var dis = !editable;
    var h = '<div class="lt-form">';
    h += '<div class="lt-row"><span class="lt-label">Mode</span><span class="lt-value">Sealed</span></div>';
    h += '<div class="lt-row"><span class="lt-label">Match length</span>' + seg('bestOf', [[1, 'Best of 1'], [3, 'Best of 3']], s.bestOf, dis) + '</div>';
    h += '<div class="lt-row"><span class="lt-label">Packs per player</span>' + select('packCount', range, s.packCount, dis) + '</div>';
    h += '<div class="lt-row"><span class="lt-label">Packs</span>' + seg('packMode', PACK_MODES, s.packMode, dis) + '</div>';
    if (s.packMode === 'single') {
      h += '<div class="lt-row"><span class="lt-label">Set</span>' + select('singleSet', d.boosterSets, s.singleSet, dis) + '</div>';
    } else if (s.packMode === 'hostchoice') {
      h += '<ol class="lt-lineup">';
      for (var i = 0; i < s.packCount; i++) {
        h += '<li class="lt-pack"><span class="lt-label">Pack ' + (i + 1) + '</span>' + select('lineup', d.boosterSets, (s.lineup || [])[i], dis, ' data-i="' + i + '"') +
          (editable ? button('lt-shuffle', '🔀', ' data-i="' + i + '" title="Pick a set at random" aria-label="Pick a set at random for pack ' + (i + 1) + '"') : '') + '</li>';
      }
      h += '</ol>';
      if (editable) h += '<div class="lt-row lt-row--tools">' + button('lt-shuffle-all', 'Shuffle all') + button('lt-reset', 'Reset') + '</div>';
    } else {
      h += '<div class="lt-row lt-row--top"><span class="lt-label">Roll from</span><div class="lt-chaos">' + d.boosterSets.map(function (code) {
        var on = (s.chaosSets || []).indexOf(code) >= 0;
        return '<label class="lt-check"><input type="checkbox" data-lt="chaos" value="' + esc(code) + '"' + (on ? ' checked' : '') + (dis ? ' disabled' : '') + '> ' + esc(code) + '</label>';
      }).join('') + '</div></div>';
      h += '<p class="lt-note">Each player’s packs are rolled once from these sets when the event starts. Repeats are allowed.</p>';
    }
    h += '</div>';
    if ((d.settingsErrors || []).length) h += '<div class="lt-err" role="alert">' + d.settingsErrors.map(esc).join('<br>') + '</div>';
    return h;
  }

  function sendSettings(ctx) {
    if (sendTimer) clearTimeout(sendTimer);
    sendTimer = setTimeout(function () {
      sendTimer = null;
      ctx.post('settings', 'settings=' + encodeURIComponent(JSON.stringify(draft)), function () { lastSig = ''; });
    }, 300);
  }

  function wireSettings(root, d, ctx) {
    var change = function (fn) { if (!draft) draft = copy(d.settings); fn(draft); lastSig = ''; SWULimitedRenderRoom(root, d, ctx); sendSettings(ctx); };
    var fit = function (s) {   // Host's Choice keeps one set per pack
      s.lineup = (s.lineup || []).slice(0, s.packCount);
      while (s.lineup.length < s.packCount) s.lineup.push(d.boosterSets[d.boosterSets.length - 1]);
    };
    root.querySelectorAll('[data-lt]').forEach(function (n) {
      n.addEventListener('change', function () {
        var k = n.getAttribute('data-lt');
        change(function (s) {
          if (k === 'bestOf' || k === 'packCount') s[k] = parseInt(n.value, 10);
          else if (k === 'packMode' || k === 'singleSet') s[k] = n.value;
          else if (k === 'lineup') s.lineup[parseInt(n.getAttribute('data-i'), 10)] = n.value;
          else if (k === 'chaos') {
            var set = (s.chaosSets || []).filter(function (x) { return x !== n.value; });
            if (n.checked) set.push(n.value);
            s.chaosSets = d.boosterSets.filter(function (x) { return set.indexOf(x) >= 0; });
          }
          fit(s);
        });
      });
    });
    var pick = function () { return d.boosterSets[Math.floor(Math.random() * d.boosterSets.length)]; };
    root.querySelectorAll('.lt-shuffle').forEach(function (b) {
      b.onclick = function () { change(function (s) { fit(s); s.lineup[parseInt(b.getAttribute('data-i'), 10)] = pick(); }); };
    });
    var all = root.querySelector('.lt-shuffle-all');
    if (all) all.onclick = function () { change(function (s) { fit(s); s.lineup = s.lineup.map(pick); }); };
    var reset = root.querySelector('.lt-reset');
    if (reset) reset.onclick = function () { change(function (s) { fit(s); s.lineup = s.lineup.map(function () { return d.boosterSets[d.boosterSets.length - 1]; }); }); };
  }

  function seatList(d) {
    return '<ul class="lt-seats">' + d.seats.map(function (s) {
      var isHost = s.playerID === d.hostPlayerID;
      return '<li class="lt-seat' + (d.me && s.playerID === d.me.playerID ? ' lt-seat--me' : '') + '"><span class="lt-name">' + esc(s.name) + '</span>' + seatChips(s, isHost) + '</li>';
    }).join('') + '</ul>';
  }

  function kickPrompts(d) {
    return (d.kick || []).filter(function (k) { return k.canVote; }).map(function (k) {
      return '<div class="lt-kick" role="alert"><span>' + (k.iVoted ? 'You voted to remove ' + esc(k.name) + ' (' + k.votes + ' of ' + k.needed + ').' : 'Kick ' + esc(k.name) + '?') + '</span>' +
        (k.iVoted ? '' : button('lt-kick-yes', 'Kick', ' data-pid="' + k.target + '"') + button('lt-kick-wait', 'Wait another 20 seconds', ' data-pid="' + k.target + '"')) + '</div>';
    }).join('');
  }

  function resultLine(d) {
    var r = d.result || {}, name = function (pid) { var s = d.seats.filter(function (x) { return x.playerID === pid; })[0]; return s ? s.name : 'Nobody'; };
    if (r.reason === 'played') {
      var ids = Object.keys(r.score || {}).map(Number);
      var w = r.winner, l = ids.filter(function (x) { return x !== w; })[0];
      return w ? esc(name(w)) + ' won ' + (r.score[w] || 0) + '–' + (r.score[l] || 0) + '.' : 'The match was drawn.';
    }
    var gone = d.seats.filter(function (s) { return s.dropped; })[0];
    var why = r.reason === 'kicked' ? ' was removed' : ' left';
    return (gone ? esc(gone.name) + why + ' — ' : '') + (r.winner ? esc(name(r.winner)) + ' wins.' : 'The event is over.');
  }

  function startClock(root, d, ctx) {
    if (clockTimer) { clearInterval(clockTimer); clockTimer = null; }
    if (d.phase !== 'building' || d.buildEndsIn == null) return;
    deadline = Date.now() + d.buildEndsIn * 1000;
    var tick = function () {
      var left = (deadline - Date.now()) / 1000, n = root.querySelector('.lt-clock');
      if (n) { n.textContent = mmss(left); n.classList.toggle('lt-clock--warn', left < 60); }
      var allReady = d.seats.filter(function (s) { return !s.dropped; }).every(function (s) { return s.ready; });
      // The start needs the engine, so the room asks the Limited endpoint to tick once it is due.
      if ((left <= 0 || allReady) && Date.now() - lastTick > 3000) { lastTick = Date.now(); ctx.post('tick', '', function () { lastSig = ''; }); }
    };
    tick();
    clockTimer = setInterval(tick, 1000);
  }

  window.SWULimitedRenderRoom = function (root, d, ctx) {
    var sig = JSON.stringify([d, draft]).replace(/"buildEndsIn":\d+/, '');
    if (sig === lastSig) return;
    // A confirmed poll replaces the host's draft once it carries the same settings.
    if (draft && JSON.stringify(draft) === JSON.stringify(d.settings) && !sendTimer) draft = null;
    lastSig = sig;
    var h = '<div class="lt-panel" data-phase="' + esc(d.phase) + '">';
    h += '<div class="lt-head"><h3 class="lt-title">Sealed</h3><span class="lt-phase">' +
      ({ setup: 'Setting up', building: 'Deckbuilding', playing: 'Playing', done: 'Finished' }[d.phase] || '') + '</span></div>';
    if (!d.me) {
      h += d.phase === 'setup'
        ? '<p class="lt-note">Join to take the second seat. No deck needed — you build one from the packs you open.</p>' + button('swu2-btn--primary lt-join', 'Join this event')
        : '<p class="lt-note">This event has already started.</p>';
      h += '</div>';
      root.innerHTML = h;
      var j = root.querySelector('.lt-join'); if (j) j.onclick = function () { j.disabled = true; ctx.join(); };
      return;
    }
    h += kickPrompts(d);
    if (d.phase === 'setup') {
      h += renderSettings(d, d.isHost);
      h += seatList(d);
      if (d.isHost) h += '<div class="lt-actions">' + button('swu2-btn--primary lt-start', 'Start Sealed', d.canStart ? '' : ' disabled') + '</div>';
      else h += '<p class="lt-note">The host chooses the settings and starts the event.</p>';
    } else if (d.phase === 'building') {
      h += '<div class="lt-build"><span class="lt-label">Deckbuilding ends in</span><span class="lt-clock">' + mmss(d.buildEndsIn || 0) + '</span></div>';
      if (d.me.lineup && d.me.lineup.length) h += '<p class="lt-note">Your packs: ' + d.me.lineup.map(esc).join(' · ') + '</p>';
      h += seatList(d);
      h += '<div class="lt-actions"><a class="btn swu2-btn swu2-btn--primary ch lt-open" href="' + esc(ctx.appBase + 'SWUSim/LimitedBuilder.php?lobbyID=' + encodeURIComponent(ctx.lobbyID)) + '">' +
        (d.me.ready ? 'View your deck' : 'Open deckbuilder') + '</a></div>';
    } else if (d.phase === 'playing') {
      h += seatList(d);
      if (d.live) h += '<div class="lt-actions">' + button('swu2-btn--primary lt-back', 'Back to your game') + '</div>';
    } else {
      h += '<div class="lt-result">' + resultLine(d) + '</div>' + seatList(d);
      h += '<div class="lt-actions"><a class="btn swu2-btn ch lt-menu" href="' + esc(ctx.appBase + 'SharedUI/MainMenu.php') + '">Main Menu</a></div>';
    }
    h += '</div>';
    root.innerHTML = h;
    if (d.phase === 'setup' && d.isHost) wireSettings(root, d, ctx);
    var st = root.querySelector('.lt-start');
    if (st) st.onclick = function () { st.disabled = true; ctx.post('start', '', function (r) { lastSig = ''; if (!r.success) st.disabled = false; }); };
    var back = root.querySelector('.lt-back');
    if (back) back.onclick = function () { ctx.backToGame(d.live); };
    root.querySelectorAll('.lt-kick-yes, .lt-kick-wait').forEach(function (b) {
      b.onclick = function () {
        b.disabled = true;
        ctx.post(b.classList.contains('lt-kick-yes') ? 'kickVote' : 'kickWait', 'targetPlayerID=' + encodeURIComponent(b.getAttribute('data-pid')), function () { lastSig = ''; });
      };
    });
    startClock(root, d, ctx);
  };
})();
