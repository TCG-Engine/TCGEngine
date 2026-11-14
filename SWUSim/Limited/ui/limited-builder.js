// SWUSim Limited deckbuilder — SWUSim/LimitedBuilder.php's client. Spec: docs/superpowers/specs/2026-11-04-swusim-limited-
// sealed-draft-design.md §3.4; mockup docs/superpowers/specs/2026-11-04-limited-mockups/deckbuilder.html.
//
// The left pane is the pool AND the sideboard: a card leaves it when added to the deck and comes back when removed; a card
// used up stays visible at ×0, greyed. Submit saves a legal deck; Ready locks it in (no edits after). Editing after Submit
// re-enables Submit and disables Ready until it is submitted again. The server (SWUSim/LimitedBuilderAction.php) is the
// judge of legality — the page only checks the shape (a leader, a base, 30 cards) to know when Submit is worth pressing.
(function () {
  'use strict';
  var CTX = window.LB_CONTEXT || {};
  var MATCH = !!CTX.matchId;
  var POLL_MS = 2000, MIN_DECK = 30;
  var DRAFT_KEY = 'tcg:limited:draft:' + (MATCH ? CTX.matchId + ':' + CTX.seat : CTX.lobbyID);

  var S = { pool: [], bases: [], cards: {}, deck: { leader: '', base: '', main: {} }, submittedSig: null, ready: false,
            endsAt: 0, tab: 'cards', view: 'pool', q: '', f: { aspect: [], type: [], arena: [], cost: [] }, loaded: false, busy: false };

  function el(id) { return document.getElementById(id); }
  function esc(t) { return String(t == null ? '' : t).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
  function appBase() { var p = location.pathname, i = p.indexOf('/TCGEngine/'); return i >= 0 ? p.slice(0, i + 11) : '/TCGEngine/'; }
  function roomKey() {
    try { var v = JSON.parse(localStorage.getItem('tcg:lobbyAuth:' + CTX.lobbyID) || 'null'); return (v && v.authKey) || ''; } catch (e) { return ''; }
  }
  function post(action, extra, cb) {
    var p = 'action=' + encodeURIComponent(action) + (MATCH
      ? '&matchId=' + encodeURIComponent(CTX.matchId) + '&playerID=' + encodeURIComponent(CTX.seat) + '&authKey=' + encodeURIComponent(CTX.authKey)
      : '&lobbyID=' + encodeURIComponent(CTX.lobbyID) + '&authKey=' + encodeURIComponent(roomKey())) + (extra || '');
    var x = new XMLHttpRequest();
    x.open('POST', appBase() + 'SWUSim/LimitedBuilderAction.php', true);
    x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    x.onload = function () { var r = {}; try { r = JSON.parse(x.responseText); } catch (e) {} cb(r); };
    x.onerror = function () { cb({}); };
    x.send(p);
  }

  // ── The deck model ────────────────────────────────────────────────────────────────────────────────────────────────
  function fact(id) { return S.cards[id] || { title: id, cost: null, aspects: [], type: '', arena: '', traits: [], text: '' }; }
  function isLeader(id) { return fact(id).type === 'Leader'; }
  function isBase(id) { return fact(id).type === 'Base'; }
  function poolCounts() { var c = {}; S.pool.forEach(function (id) { if (!isLeader(id) && !isBase(id)) c[id] = (c[id] || 0) + 1; }); return c; }
  function mainList() { var out = []; Object.keys(S.deck.main).forEach(function (id) { for (var i = 0; i < S.deck.main[id]; i++) out.push(id); }); return out; }
  function mainCount() { return mainList().length; }
  function sig(d) { var m = Object.keys(d.main).filter(function (k) { return d.main[k] > 0; }).sort().map(function (k) { return k + 'x' + d.main[k]; }); return d.leader + '|' + d.base + '|' + m.join(','); }
  function fromServer(d) { var main = {}; (d && d.main || []).forEach(function (id) { main[id] = (main[id] || 0) + 1; }); return { leader: (d && d.leader) || '', base: (d && d.base) || '', main: main }; }
  function dirty() { return sig(S.deck) !== S.submittedSig; }
  function shapeOk() { return !!S.deck.leader && !!S.deck.base && mainCount() >= MIN_DECK; }
  function byCost(a, b) {
    var fa = fact(a), fb = fact(b), ca = fa.cost == null ? 99 : fa.cost, cb = fb.cost == null ? 99 : fb.cost;
    return ca - cb || String(fa.title).localeCompare(String(fb.title)) || a.localeCompare(b);
  }
  // Everything is laid out by ASPECT GROUP (owner, 2026-11-05): the card's aspects in the game's order — Vigilance,
  // Command, Aggression, Cunning, then Heroism, Villainy — so Vigilance, Vigilance · Heroism, Vigilance · Villainy,
  // Command, … and aspect-less cards last; by cost and title inside a group.
  var ASPECT_ORDER = ['Vigilance', 'Command', 'Aggression', 'Cunning', 'Heroism', 'Villainy'];
  function aspectKey(id) {
    var k = (fact(id).aspects || []).map(function (a) { var i = ASPECT_ORDER.indexOf(a); return i < 0 ? 98 : i; }).sort(function (x, y) { return x - y; });
    return k.length ? k : [99];
  }
  function aspectGroup(id) {
    var k = aspectKey(id);
    return k[0] === 99 ? 'No aspect' : k.map(function (i) { return ASPECT_ORDER[i] || 'Other'; }).join(' · ');
  }
  function byAspect(a, b) {
    var ka = aspectKey(a), kb = aspectKey(b);
    for (var i = 0; i < Math.max(ka.length, kb.length); i++) {
      if (ka[i] === undefined) return -1;   // a group before its own two-aspect groups (Vigilance before Vigilance · Heroism)
      if (kb[i] === undefined) return 1;
      if (ka[i] !== kb[i]) return ka[i] - kb[i];
    }
    return byCost(a, b);
  }
  // Tiles in aspect order, each group opened by a full-width label.
  function grouped(ids, tileFor) {
    var out = '', last = null;
    ids.slice().sort(byAspect).forEach(function (id) {
      var g = aspectGroup(id);
      if (g !== last) { out += '<h4 class="lb-group">' + esc(g) + '</h4>'; last = g; }
      out += tileFor(id);
    });
    return out;
  }
  function saveDraft() { try { localStorage.setItem(DRAFT_KEY, JSON.stringify(S.deck)); } catch (e) {} }
  function loadDraft() { try { var v = JSON.parse(localStorage.getItem(DRAFT_KEY) || 'null'); return v && typeof v.main === 'object' ? v : null; } catch (e) { return null; } }

  // ── Filters ───────────────────────────────────────────────────────────────────────────────────────────────────────
  function costBucket(c) { if (c == null) return ''; return c <= 2 ? '0-2' : (c <= 4 ? '3-4' : '5+'); }
  function matches(id) {
    var f = fact(id), q = S.q.trim().toLowerCase();
    if (q) {
      var hay = [f.title, f.subtitle, (f.traits || []).join(' '), f.text].join(' ').toLowerCase();
      if (hay.indexOf(q) < 0) return false;
    }
    // "All of" (owner, 2026-11-05): a card must carry EVERY selected aspect — Vigilance + Heroism shows only cards with both.
    if (S.f.aspect.length && !S.f.aspect.every(function (a) { return (f.aspects || []).indexOf(a) >= 0; })) return false;
    if (S.f.type.length && S.f.type.indexOf(f.type) < 0) return false;
    if (S.f.arena.length && S.f.arena.indexOf(f.arena) < 0) return false;
    if (S.f.cost.length && S.f.cost.indexOf(costBucket(f.cost)) < 0) return false;
    return true;
  }

  // ── Rendering ─────────────────────────────────────────────────────────────────────────────────────────────────────
  function tile(id, count, cls, kind) {
    var f = fact(id), name = f.title + (f.subtitle ? ', ' + f.subtitle : '');
    return '<div role="button" tabindex="0" class="lb-card ' + (cls || '') + '" data-id="' + esc(id) + '" data-kind="' + kind + '" title="' + esc(name) + '">' +
      '<img src="' + esc(window.swuCardArtUrl ? window.swuCardArtUrl(id, 'tile') : '') + '" alt="' + esc(name) + '" loading="lazy">' +
      (count == null ? '' : '<span class="lb-qty">×' + count + '</span>') + '</div>';
  }
  function renderPool() {
    var html = '';
    if (S.tab === 'leaders') {
      var seen = {};
      S.pool.filter(isLeader).forEach(function (id) { seen[id] = (seen[id] || 0) + 1; });
      html = grouped(Object.keys(seen).filter(matches), function (id) { return tile(id, null, id === S.deck.leader ? 'lb-card--on' : '', 'leader'); });
    } else if (S.tab === 'bases') {
      html = grouped(S.bases.filter(matches), function (id) { return tile(id, null, id === S.deck.base ? 'lb-card--on' : '', 'base'); });
    } else {
      var have = poolCounts();
      html = grouped(Object.keys(have).filter(matches), function (id) {
        var left = have[id] - (S.deck.main[id] || 0);
        return tile(id, left, left <= 0 ? 'lb-card--used' : '', 'pool');
      });
    }
    el('lbPoolGrid').innerHTML = html || '<p class="lb-empty">Nothing matches.</p>';
  }
  function renderSlot(node, id, label) {
    if (!id) { node.innerHTML = '<span class="lb-slot-empty">' + label + '</span>'; node.classList.remove('lb-slot--full'); return; }
    var f = fact(id);
    node.innerHTML = '<img src="' + esc(window.swuCardArtUrl ? window.swuCardArtUrl(id, 'card') : '') + '" alt="' + esc(label + ': ' + f.title) + '">';
    node.title = label + ': ' + f.title + (f.subtitle ? ', ' + f.subtitle : '');
    node.classList.add('lb-slot--full');
  }
  function renderCurve() {
    var buckets = [0, 0, 0, 0, 0, 0, 0, 0];
    mainList().forEach(function (id) { var c = fact(id).cost; if (c != null) buckets[Math.min(7, c)]++; });
    var max = Math.max.apply(null, buckets.concat([1]));
    el('lbCurve').innerHTML = buckets.map(function (n, i) {
      return '<span class="lb-bar-col" title="' + n + ' card' + (n === 1 ? '' : 's') + ' at cost ' + (i === 7 ? '7+' : i) + '">' +
        '<span class="lb-bar-n">' + (n || '') + '</span><span class="lb-bar-fill" style="height:' + Math.round(48 * n / max) + 'px"></span>' +
        '<span class="lb-bar-c">' + (i === 7 ? '7+' : i) + '</span></span>';
    }).join('');
  }
  function renderDeck() {
    renderSlot(el('lbLeaderSlot'), S.deck.leader, 'Leader');
    renderSlot(el('lbBaseSlot'), S.deck.base, 'Base');
    var ids = Object.keys(S.deck.main).filter(function (k) { return S.deck.main[k] > 0; });
    el('lbDeckGrid').innerHTML = grouped(ids, function (id) { return tile(id, S.deck.main[id], '', 'deck'); }) || '<p class="lb-empty">Your deck is empty.</p>';
    renderCurve();
    var n = mainCount(), have = poolCounts(), side = 0;
    Object.keys(have).forEach(function (id) { side += Math.max(0, have[id] - (S.deck.main[id] || 0)); });
    el('lbDeckCount').textContent = n; el('lbFootCount').textContent = n; el('lbSwitchCount').textContent = n;
    el('lbSideCount').textContent = side;
    el('lbLegal').textContent = shapeOk() ? '✓ ready to submit' : '';
    el('lbLeaderCount').textContent = Object.keys(S.pool.filter(isLeader).reduce(function (o, k) { o[k] = 1; return o; }, {})).length;
    el('lbBaseCount').textContent = S.bases.length;
    el('lbCardsLeft').textContent = side;
  }
  function renderButtons() {
    el('lbSubmit').disabled = S.ready || S.busy || !dirty() || !shapeOk();
    el('lbReady').disabled = S.ready || S.busy || S.submittedSig === null || dirty();
    document.body.classList.toggle('lb-is-ready', S.ready);
    el('lbStatus').textContent = S.ready ? 'Ready — waiting for your opponent' : (S.submittedSig !== null && !dirty() ? 'Submitted — press Ready to lock it in' : (dirty() && S.submittedSig !== null ? 'Changed since you submitted' : ''));
  }
  function render() { renderPool(); renderDeck(); renderButtons(); }

  function tickClock() {
    var t = el('lbTimer'), ft = el('lbFootTimer');
    if (!S.endsAt) { t.textContent = ''; ft.textContent = ''; return; }
    var left = Math.max(0, Math.round((S.endsAt - Date.now()) / 1000));
    var txt = Math.floor(left / 60) + ':' + ('0' + (left % 60)).slice(-2);
    t.textContent = S.phase === 'between' ? 'Next round in ' + txt : txt + ' left'; ft.textContent = txt;   // between Swiss rounds
    t.classList.toggle('lb-timer--warn', left < 60); ft.classList.toggle('lb-timer--warn', left < 60);
  }

  // ── Editing ───────────────────────────────────────────────────────────────────────────────────────────────────────
  function edit(fn) { if (S.ready) return; fn(); saveDraft(); el('lbErrors').innerHTML = ''; render(); }
  function onCard(btn) {
    var id = btn.getAttribute('data-id'), kind = btn.getAttribute('data-kind');
    if (kind === 'leader') edit(function () { S.deck.leader = id; });
    else if (kind === 'base') edit(function () { S.deck.base = id; });
    else if (kind === 'pool') {
      var left = (poolCounts()[id] || 0) - (S.deck.main[id] || 0);
      if (left > 0) edit(function () { S.deck.main[id] = (S.deck.main[id] || 0) + 1; });
    } else if (kind === 'deck') edit(function () { S.deck.main[id]--; if (S.deck.main[id] <= 0) delete S.deck.main[id]; });
  }

  // ── Hover / long-press preview (the sideboard screen's pattern, Sideboard.php wireSbPreview) ──────────────────────
  var preview = null, previewTimer = 0, longPressed = false;
  function ensurePreview() { if (!preview) { preview = document.createElement('img'); preview.id = 'lbPreview'; document.body.appendChild(preview); } return preview; }
  function placePreview(ev) {
    if (!preview || preview.style.display !== 'block') return;
    var pad = 18, w = preview.offsetWidth || 300, h = preview.offsetHeight || 420, x = ev.clientX + pad, y = ev.clientY + pad;
    if (x + w > window.innerWidth) x = ev.clientX - w - pad;
    if (x < 8) x = 8;
    if (y + h > window.innerHeight) y = window.innerHeight - h - 8;
    if (y < 8) y = 8;
    preview.style.left = x + 'px'; preview.style.top = y + 'px';
  }
  function hidePreview() { clearTimeout(previewTimer); previewTimer = 0; if (preview) preview.style.display = 'none'; }
  function wirePreview(root) {
    var last = null;
    var later = function (node, ev) {
      last = { clientX: ev.clientX, clientY: ev.clientY }; clearTimeout(previewTimer);
      previewTimer = setTimeout(function () {
        previewTimer = 0;
        var p = ensurePreview(); p.src = window.swuCardArtUrl ? window.swuCardArtUrl(node.getAttribute('data-id'), 'card') : ''; p.style.display = 'block'; placePreview(last);
        if (ev.pointerType === 'touch') longPressed = true;
      }, SWUSIM_CARD_PREVIEW_HOVER_MS);   // Core/SWUSimCardPreviewDelay.js
    };
    root.addEventListener('pointerover', function (ev) { var n = ev.target.closest('.lb-card'); if (n && ev.pointerType !== 'touch' && !n.contains(ev.relatedTarget)) later(n, ev); });
    root.addEventListener('pointermove', function (ev) { if (ev.pointerType !== 'touch') { last = { clientX: ev.clientX, clientY: ev.clientY }; placePreview(ev); } });
    root.addEventListener('pointerout', function (ev) { var n = ev.target.closest('.lb-card'); if (n && ev.pointerType !== 'touch' && !n.contains(ev.relatedTarget)) hidePreview(); });
    root.addEventListener('pointerdown', function (ev) { var n = ev.target.closest('.lb-card'); if (n && ev.pointerType === 'touch') { longPressed = false; later(n, ev); } });
    ['pointerup', 'pointercancel'].forEach(function (t) { root.addEventListener(t, function (ev) { if (ev.pointerType === 'touch') hidePreview(); }); });
    root.addEventListener('contextmenu', function (ev) { if (ev.target.closest('.lb-card') && (ev.pointerType === 'touch' || longPressed)) ev.preventDefault(); });
  }

  // ── Server ────────────────────────────────────────────────────────────────────────────────────────────────────────
  function leave(gameName) {
    S.leaving = true;
    if (MATCH) location.href = appBase() + 'NextTurn.php?gameName=' + encodeURIComponent(gameName) + '&playerID=' + encodeURIComponent(CTX.seat) +
      '&authKey=' + encodeURIComponent(CTX.authKey) + '&folderPath=SWUSim&viewerPerspective=' + encodeURIComponent(CTX.seat);
    else location.href = appBase() + 'SharedUI/WaitingRoom.php?lobby=' + encodeURIComponent(CTX.lobbyID);   // the room sends the seat on
  }
  function applyState(r) {
    if (!r.success) { el('lbStatus').textContent = r.message || 'This deckbuilder is not available.'; return; }
    S.endsAt = r.endsIn == null ? 0 : Date.now() + r.endsIn * 1000;
    S.phase = r.phase || ''; S.draftPod = r.mode === 'draft';
    tickClock();
    el('lbOpp').textContent = r.opponentReady ? (S.draftPod ? 'Everyone else is Ready' : 'Your opponent is Ready') : '';
    if (r.gameName && !S.leaving) { leave(r.gameName); return; }
    // A Draft pod's round began and this seat has a bye (or the event ended): the room is where it waits.
    if (!MATCH && S.draftPod && !S.leaving && (r.bye || r.phase === 'playing' || r.phase === 'done')) { leave(''); return; }
    if (!MATCH && r.playerID && S.attachChat) S.attachChat(r.playerID);
    if (!S.loaded) {
      S.loaded = true;
      S.pool = r.pool || []; S.bases = r.basesInPlay || []; S.cards = r.cards || {};
      var server = fromServer(r.deck);
      // In a Bo3 the last game's deck is already playable (Ready keeps it), so it counts as submitted.
      S.submittedSig = (r.submitted || MATCH) && r.deck ? sig(server) : null;
      var draft = r.ready ? null : loadDraft();
      S.deck = draft || server;
    }
    S.ready = !!r.ready;
    if (S.ready) { S.deck = fromServer(r.deck); S.submittedSig = sig(S.deck); }
    render();
  }
  function poll() {
    if (S.leaving) return;
    post('state', '', function (r) { applyState(r); setTimeout(poll, POLL_MS); });
  }
  function submit() {
    S.busy = true; renderButtons();
    var d = { leader: S.deck.leader, base: S.deck.base, main: mainList() };
    post('submit', '&deck=' + encodeURIComponent(JSON.stringify(d)), function (r) {
      S.busy = false;
      if (r.success) { S.submittedSig = sig(S.deck); el('lbErrors').innerHTML = ''; }
      else el('lbErrors').innerHTML = (r.errors && r.errors.length ? r.errors : [r.message || 'That did not work — try again.']).map(function (e) { return '<li>' + esc(e) + '</li>'; }).join('');
      render();
    });
  }
  function ready() {
    var go = function () {
      S.busy = true; renderButtons();
      post('ready', '', function (r) {
        S.busy = false;
        if (r.success) {
          S.ready = true; try { localStorage.removeItem(DRAFT_KEY); } catch (e) {}
          if (!MATCH && S.phase === 'between') { leave(''); return; }   // between Swiss rounds the room holds Ready and Start round
        }
        else el('lbErrors').innerHTML = '<li>' + esc(r.message || 'That did not work — try again.') + '</li>';
        render();
      });
    };
    var ask = 'Lock in this deck? You can’t edit it after Ready.';
    if (window.StyledConfirm) window.StyledConfirm(ask, { title: 'Ready', confirmLabel: 'Ready' }).then(function (ok) { if (ok) go(); });
    else if (window.confirm(ask)) go();
  }

  // ── Wiring ────────────────────────────────────────────────────────────────────────────────────────────────────────
  document.addEventListener('click', function (ev) {
    var card = ev.target.closest('.lb-card');
    if (card) { if (longPressed) { longPressed = false; return; } hidePreview(); onCard(card); return; }
    var slot = ev.target.closest('.lb-slot');
    if (slot && !S.ready) {
      var kind = slot.getAttribute('data-kind');
      edit(function () { S.deck[kind] = ''; });
      S.tab = kind === 'leader' ? 'leaders' : 'bases';
      el('lb-tab-' + S.tab).checked = true;
      if (S.view === 'deck') { S.view = 'pool'; el('lb-view-pool').checked = true; document.querySelector('.lb-cols').setAttribute('data-view', 'pool'); }
      renderPool();
      return;
    }
    var tog = ev.target.closest('.lb-tog');
    if (tog) {
      var f = tog.getAttribute('data-f'), v = tog.getAttribute('data-v'), list = S.f[f], at = list.indexOf(v);
      if (at >= 0) list.splice(at, 1); else list.push(v);
      tog.setAttribute('aria-pressed', at >= 0 ? 'false' : 'true');
      renderPool();
    }
  });
  // The tiles and slots are role="button" divs (a real <button> drags in the site's .btn chrome): Enter / Space click them.
  document.addEventListener('keydown', function (ev) {
    if ((ev.key === 'Enter' || ev.key === ' ') && ev.target.closest && ev.target.closest('.lb-card, .lb-slot')) { ev.preventDefault(); ev.target.click(); }
  });
  document.querySelectorAll('input[name="lb-tab"]').forEach(function (n) { n.addEventListener('change', function () { S.tab = n.value; renderPool(); }); });
  document.querySelectorAll('input[name="lb-view"]').forEach(function (n) {
    n.addEventListener('change', function () { S.view = n.value; document.querySelector('.lb-cols').setAttribute('data-view', n.value); });
  });
  el('lbSearch').addEventListener('input', function () { S.q = el('lbSearch').value; renderPool(); });
  el('lbSubmit').addEventListener('click', submit);
  el('lbReady').addEventListener('click', ready);
  wirePreview(document.querySelector('.lb-root'));
  setInterval(tickClock, 1000);

  // The chat sends AS this seat, and the lobby scope trusts the posted playerID — so in the build context it waits for the
  // first state, which names the caller's own lobby seat (attachChat runs from applyState).
  function attachChat(playerID) {
    if (S.chatAttached || !window.TCGChatPanel || !playerID) return;
    S.chatAttached = true;
    window.TCGChatPanel.attach({
      scope: MATCH ? { matchId: CTX.matchId } : { lobbyID: CTX.lobbyID },
      playerID: playerID,
      authKey: MATCH ? CTX.authKey : roomKey(),
      folderPath: 'SWUSim', canSend: !!CTX.canChat, cannotSendReason: CTX.cannotChatReason || ''
    });
  }
  S.attachChat = attachChat;
  if (MATCH) attachChat(CTX.seat);
  poll();
})();
