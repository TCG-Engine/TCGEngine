// SWUSim Limited Draft — the pick screen (SWUSim/LimitedDraft.php). Spec: docs/superpowers/specs/2026-11-04-swusim-limited-
// sealed-draft-design.md §3.2; mockup docs/superpowers/specs/2026-11-04-limited-mockups/draft-pick.html.
//
// Polls SWUSim/LimitedDraftAction.php every 1.5 s (state is also the seat's heartbeat and the draft's clock). A click only
// SELECTS a card; "Pick <title>" confirms — so a misclick never costs a pick — and a pick names its pack, so a double
// click or a stale tab can never pick twice. Cards are laid out in the builder's aspect-group order.
(function () {
  'use strict';
  var CTX = window.LD_CONTEXT || {};
  var POLL_MS = 1500;
  var ASPECT_ORDER = ['Vigilance', 'Command', 'Aggression', 'Cunning', 'Heroism', 'Villainy'];
  var RARITY = { Common: 'C', Uncommon: 'U', Rare: 'R', Legendary: 'L', Special: 'S' };
  var S = { st: null, selected: '', busy: false, endsAt: 0, leaving: false, chat: false };

  function el(id) { return document.getElementById(id); }
  // Polls repeat the same state; rewriting identical markup would drop focus and can swallow a click mid-press.
  function setHTML(node, html) { if (node.__html !== html) { node.__html = html; node.innerHTML = html; } }
  function esc(t) { return String(t == null ? '' : t).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
  function appBase() { var p = location.pathname, i = p.indexOf('/TCGEngine/'); return i >= 0 ? p.slice(0, i + 11) : '/TCGEngine/'; }
  function roomKey() { try { var v = JSON.parse(localStorage.getItem('tcg:lobbyAuth:' + CTX.lobbyID) || 'null'); return (v && v.authKey) || ''; } catch (e) { return ''; } }
  function post(action, extra, cb) {
    var x = new XMLHttpRequest();
    x.open('POST', appBase() + 'SWUSim/LimitedDraftAction.php', true);
    x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    x.onload = function () { var r = {}; try { r = JSON.parse(x.responseText); } catch (e) {} cb(r); };
    x.onerror = function () { cb({}); };
    x.send('action=' + encodeURIComponent(action) + '&lobbyID=' + encodeURIComponent(CTX.lobbyID) + '&authKey=' + encodeURIComponent(roomKey()) + (extra || ''));
  }
  function fact(id) { return (S.st && S.st.cards && S.st.cards[id]) || { title: id, aspects: [], cost: null, rarity: '' }; }
  function name(id) { var f = fact(id); return f.title + (f.subtitle ? ', ' + f.subtitle : ''); }

  // The builder's aspect-group order (limited-builder.js): the game's aspect order, single aspects before their pairs,
  // aspect-less last; by cost and title inside a group.
  function aspectKey(id) {
    var k = (fact(id).aspects || []).map(function (a) { var i = ASPECT_ORDER.indexOf(a); return i < 0 ? 98 : i; }).sort(function (x, y) { return x - y; });
    return k.length ? k : [99];
  }
  function byAspect(a, b) {
    var ka = aspectKey(a), kb = aspectKey(b);
    for (var i = 0; i < Math.max(ka.length, kb.length); i++) {
      if (ka[i] === undefined) return -1;
      if (kb[i] === undefined) return 1;
      if (ka[i] !== kb[i]) return ka[i] - kb[i];
    }
    var fa = fact(a), fb = fact(b), ca = fa.cost == null ? 99 : fa.cost, cb = fb.cost == null ? 99 : fb.cost;
    return ca - cb || String(fa.title).localeCompare(String(fb.title)) || a.localeCompare(b);
  }

  // ── Rendering ─────────────────────────────────────────────────────────────────────────────────────────────────────
  function renderTop(st) {
    var title = st.stage === 'leaders' ? 'Leader draft' : 'Pack ' + st.round + (st.packSet ? ' (' + st.packSet + ')' : '');
    if (st.front && st.pickNo) title += ' · Pick ' + st.pickNo + ' of ' + st.packSize;
    el('ldTitle').textContent = title;
    el('ldDir').textContent = st.direction === 'right' ? 'passing ◀ right' : 'passing left ▶';
    var status;
    if (st.front) status = 'Waiting on you' + (st.queued ? ' · ' + st.queued + ' more pack' + (st.queued === 1 ? '' : 's') + ' queued behind this one' : '');
    else {
      var from = (st.pod || []).filter(function (p) { return p.queued > 0; }).map(function (p) { return p.name; });
      status = 'Waiting' + (from.length ? ' for ' + from.slice(0, 3).join(', ') + (from.length > 3 ? '…' : '') : '') + '…';
    }
    el('ldStatus').textContent = status;
  }
  function renderPod(st) {
    setHTML(el('ldPod'), (st.pod || []).map(function (p) {
      var cls = 'ld-seat' + (p.me ? ' ld-seat--me' : '') + (p.queued >= 2 ? ' ld-seat--slow' : '') + (p.dropped ? ' ld-seat--gone' : '');
      return '<li class="' + cls + '">' + esc(p.me ? 'You' : p.name) + (p.bot ? ' <span class="ld-bot">Bot</span>' : '') + '\u00a0<span class="ld-q">(' + p.queued + ')</span>' + (p.queued >= 2 ? ' ⏳' : '') + '</li>';
    }).join(''));
  }
  // Every seat's drafted leaders, in seating order (public — they change how the rest of the pod drafts).
  function renderPodLeaders(st) {
    setHTML(el('ldPodLeadsBody'), (st.pod || []).map(function (p) {
      var list = (p.leaders || []).map(function (id) {
        return '<img class="ld-plead" data-id="' + esc(id) + '" title="' + esc(name(id)) + '" alt="' + esc(name(id)) + '" src="' +
          esc(window.swuCardArtUrl ? window.swuCardArtUrl(id, 'tile') : '') + '" loading="lazy">';
      }).join('');
      return '<div class="ld-pl-row' + (p.me ? ' ld-pl-row--me' : '') + (p.dropped ? ' ld-pl-row--gone' : '') + '"><span class="ld-pl-name">' +
        esc(p.me ? 'You' : p.name) + '</span><span class="ld-pl-list">' + (list || '<span class="ld-pl-none">—</span>') + '</span></div>';
    }).join(''));
  }
  function renderPack(st) {
    var ids = st.front ? st.front.cards.slice().sort(byAspect) : [];
    if (S.selected && ids.indexOf(S.selected) < 0) S.selected = '';
    setHTML(el('ldPack'), ids.length ? ids.map(function (id) {
      var f = fact(id);
      return '<div role="button" tabindex="0" class="ld-card' + (id === S.selected ? ' ld-card--sel' : '') + '" data-id="' + esc(id) + '" title="' + esc(name(id)) + '">' +
        '<img src="' + esc(window.swuCardArtUrl ? window.swuCardArtUrl(id, 'tile') : '') + '" alt="' + esc(name(id)) + '" loading="lazy">' +
        '<span class="ld-rar">' + esc(RARITY[f.rarity] || '') + '</span></div>';
    }).join('') : '<p class="ld-empty">No pack in front of you right now.</p>');
    var b = el('ldPick');
    b.disabled = !S.selected || S.busy;
    b.textContent = S.selected ? 'Pick ' + fact(S.selected).title : 'Pick';
  }
  function renderSide(st) {
    var mine = st.mine || { leaders: [], picks: [] }, n = mine.leaders.length + mine.picks.length;
    el('ldPickCount').textContent = n; el('ldPickCount2').textContent = n;
    setHTML(el('ldLeaders'), mine.leaders.length ? 'Leaders: ' + mine.leaders.map(function (id) {
      return '<span class="ld-pick-name" data-id="' + esc(id) + '">' + esc(fact(id).title) + '</span>';
    }).join(' · ') : 'No leaders yet.');
    var counts = (st.lean && st.lean.counts) || {}, max = 1;
    ASPECT_ORDER.forEach(function (a) { max = Math.max(max, counts[a] || 0); });
    setHTML(el('ldLean'), ASPECT_ORDER.map(function (a) {
      return '<span class="ld-bar" title="' + a + ': ' + (counts[a] || 0) + '"><span class="ld-bar-fill ld-bar--' + a.toLowerCase() + '" style="height:' +
        Math.round(48 * (counts[a] || 0) / max) + 'px"></span><span class="ld-bar-c">' + a.slice(0, 3) + '</span></span>';
    }).join(''));
    var pair = (st.lean && st.lean.pair) || [];
    el('ldLeaning').textContent = pair.length ? 'Leaning: ' + pair.join(' / ') + ' — a timeout auto-pick follows this lean.' : '';
    setHTML(el('ldAll'), mine.picks.slice().sort(byAspect).map(function (id) { return '<div class="ld-all-row ld-pick-name" data-id="' + esc(id) + '">' + esc(name(id)) + '</div>'; }).join('') || '<p class="ld-empty">No picks yet.</p>');
  }
  // The kick vote (spec §3.6): a seat silent 30 s, or auto-picked once, can be voted out — a true majority of the others.
  function renderKicks(st) {
    setHTML(el('ldKicks'), (st.kick || []).filter(function (k) { return k.canVote; }).map(function (k) {
      return '<div class="ld-kick" role="alert"><span>' + (k.iVoted ? 'You voted to remove ' + esc(k.name) + ' (' + k.votes + ' of ' + k.needed + ').'
        : 'Kick ' + esc(k.name) + '? They have stopped picking.') + '</span>' + (k.iVoted ? '' :
        '<button type="button" class="btn swu2-btn ch ld-kick-yes" data-pid="' + k.target + '">Kick</button>' +
        '<button type="button" class="btn swu2-btn ch ld-kick-wait" data-pid="' + k.target + '">Wait another 20 seconds</button>') + '</div>';
    }).join(''));
  }
  function kickPost(action, pid) {
    var x = new XMLHttpRequest();
    x.open('POST', appBase() + 'SWUSim/LimitedAction.php', true);
    x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    x.send('action=' + action + '&lobbyID=' + encodeURIComponent(CTX.lobbyID) + '&authKey=' + encodeURIComponent(roomKey()) + '&targetPlayerID=' + encodeURIComponent(pid));
  }
  function tickClock() {
    var c = el('ldClock');
    if (!S.endsAt) { c.textContent = ''; return; }
    var left = Math.max(0, Math.round((S.endsAt - Date.now()) / 1000));
    c.textContent = Math.floor(left / 60) + ':' + ('0' + (left % 60)).slice(-2);
    c.classList.toggle('ld-clock--warn', left < 60);
  }
  function apply(st) {
    if (!st || !st.success && !st.phase) { el('ldError').textContent = (st && st.message) || 'The draft is not reachable right now.'; return; }
    el('ldError').textContent = st.success ? '' : (st.message || '');
    if (!S.chat && window.TCGChatPanel && st.playerID) {
      S.chat = true;
      window.TCGChatPanel.attach({ scope: { lobbyID: CTX.lobbyID }, playerID: st.playerID, authKey: roomKey(), folderPath: 'SWUSim',
                                   canSend: !!CTX.canChat, cannotSendReason: CTX.cannotChatReason || '' });
    }
    if (st.dropped) {   // kicked by the pod (or left): the seat auto-picks to the end, so there is nothing to do here
      S.leaving = true; S.endsAt = 0; tickClock();
      setHTML(el('ldPack'), '<p class="ld-empty">You were removed from this draft. <a href="' + appBase() + 'SharedUI/WaitingRoom.php?lobby=' +
        encodeURIComponent(CTX.lobbyID) + '">Back to the room</a></p>');
      el('ldStatus').textContent = 'You were removed from this draft.'; el('ldPick').disabled = true; setHTML(el('ldKicks'), '');
      return;
    }
    if (st.phase === 'building') { S.leaving = true; location.href = appBase() + 'SWUSim/LimitedBuilder.php?lobbyID=' + encodeURIComponent(CTX.lobbyID); return; }
    if (st.phase !== 'drafting') { S.leaving = true; location.href = appBase() + 'SharedUI/WaitingRoom.php?lobby=' + encodeURIComponent(CTX.lobbyID); return; }
    S.st = st;
    S.endsAt = st.endsIn == null ? 0 : Date.now() + st.endsIn * 1000;
    renderTop(st); renderPod(st); renderPodLeaders(st); renderKicks(st); renderPack(st); renderSide(st); tickClock();
  }
  function poll() {
    if (S.leaving) return;
    post('state', '', function (r) { if (!S.busy) apply(r); setTimeout(poll, POLL_MS); });
  }
  function pick() {
    if (!S.selected || S.busy || !S.st || !S.st.front) return;
    S.busy = true; el('ldPick').disabled = true;
    post('pick', '&packId=' + encodeURIComponent(S.st.front.packId) + '&cardId=' + encodeURIComponent(S.selected), function (r) {
      S.busy = false; S.selected = '';
      apply(r);
    });
  }

  // ── Hover / long-press preview (the builder's pattern) ────────────────────────────────────────────────────────────
  var preview = null, previewTimer = 0, longPressed = false;
  function ensurePreview() { if (!preview) { preview = document.createElement('img'); preview.id = 'ldPreview'; document.body.appendChild(preview); } return preview; }
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
  // A NAME in your picks (the leader line, "Show all picks") previews sooner than a tile: owner 2026-11-05, 350 ms.
  var PREVIEWABLE = '.ld-card, .ld-plead, .ld-pick-name';
  function wirePreview(root) {
    var last = null;
    var later = function (node, ev) {
      last = { clientX: ev.clientX, clientY: ev.clientY }; clearTimeout(previewTimer);
      previewTimer = setTimeout(function () {
        previewTimer = 0;
        var p = ensurePreview(); p.src = window.swuCardArtUrl ? window.swuCardArtUrl(node.getAttribute('data-id'), 'card') : ''; p.style.display = 'block'; placePreview(last);
        if (ev.pointerType === 'touch') longPressed = true;
      }, SWUSIM_CARD_PREVIEW_HOVER_MS);   // Core/SWUSimCardPreviewDelay.js — cards and pick names alike
    };
    root.addEventListener('pointerover', function (ev) { var n = ev.target.closest(PREVIEWABLE); if (n && ev.pointerType !== 'touch' && !n.contains(ev.relatedTarget)) later(n, ev); });
    root.addEventListener('pointermove', function (ev) { if (ev.pointerType !== 'touch') { last = { clientX: ev.clientX, clientY: ev.clientY }; placePreview(ev); } });
    root.addEventListener('pointerout', function (ev) { var n = ev.target.closest(PREVIEWABLE); if (n && ev.pointerType !== 'touch' && !n.contains(ev.relatedTarget)) hidePreview(); });
    root.addEventListener('pointerdown', function (ev) { var n = ev.target.closest(PREVIEWABLE); if (n && ev.pointerType === 'touch') { longPressed = false; later(n, ev); } });
    ['pointerup', 'pointercancel'].forEach(function (t) { root.addEventListener(t, function (ev) { if (ev.pointerType === 'touch') hidePreview(); }); });
    root.addEventListener('contextmenu', function (ev) { if (ev.target.closest(PREVIEWABLE) && (ev.pointerType === 'touch' || longPressed)) ev.preventDefault(); });
  }

  // ── Wiring ────────────────────────────────────────────────────────────────────────────────────────────────────────
  document.addEventListener('click', function (ev) {
    var card = ev.target.closest('.ld-card');
    if (card) { if (longPressed) { longPressed = false; return; } hidePreview(); S.selected = card.getAttribute('data-id'); if (S.st) renderPack(S.st); return; }
    var kick = ev.target.closest('.ld-kick-yes, .ld-kick-wait');
    if (kick) { kick.disabled = true; kickPost(kick.classList.contains('ld-kick-yes') ? 'kickVote' : 'kickWait', kick.getAttribute('data-pid')); }
  });
  document.addEventListener('keydown', function (ev) {
    if ((ev.key === 'Enter' || ev.key === ' ') && ev.target.closest && ev.target.closest('.ld-card')) { ev.preventDefault(); ev.target.click(); }
  });
  el('ldPick').addEventListener('click', pick);
  el('ldPodLeadsToggle').addEventListener('click', function () {
    var open = el('ldPodLeads').classList.toggle('ld-podleads--open');
    el('ldPodLeadsToggle').setAttribute('aria-expanded', open ? 'true' : 'false');
  });
  el('ldSideToggle').addEventListener('click', function () {
    var open = el('ldSide').classList.toggle('ld-side--open');
    el('ldSideToggle').setAttribute('aria-expanded', open ? 'true' : 'false');
  });
  el('ldAllToggle').addEventListener('click', function () {
    var all = el('ldAll'); all.hidden = !all.hidden;
    el('ldAllToggle').setAttribute('aria-expanded', all.hidden ? 'false' : 'true');
    el('ldAllToggle').textContent = all.hidden ? 'Show all picks' : 'Hide all picks';
  });
  wirePreview(document.querySelector('.ld-root'));
  setInterval(tickClock, 1000);
  poll();
})();
