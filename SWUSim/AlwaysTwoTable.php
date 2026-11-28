<?php
// 1v1 Always Two — the 2×2 TABLE (docs/superpowers/specs/2026-11-20-swusim-always-two-design.md, "The 2×2 table" — owner,
// 2026-11-20: "put all the players' boards on the gameboard", bases on the inside corners).
//
// One person plays two boards (seat 1 = boards 1+3, seat 2 = boards 2+4). Their game page is a table of two REAL board views — the
// unmodified NextTurn.php board for each board number, in the phone-style layout that suits a tall column — so every click, prompt,
// hand card, attack and initiative control already works and already sends the right board as playerID (both boards share the
// seat's auth key — Core/GameAuth.php). Each half opens on the existing MATCHUP view against the opponent board it faces (swuFace),
// so a column is "your board under the opponent above it". Desktop: side by side. Narrow screens: stacked, the page scrolls.
// Roles (swuEmbed): the LEFT half (board seat) is 'a2side' — silent, no sidebar; the RIGHT half (board seat+2) is 'a2' — it carries
// round / undo / log / chat at the table's right edge, plus the chime, favicon and game-over overlay (GameLayoutShared.php).

// Called from the top of NextTurn.php, BEFORE any output, only for folderPath=SWUSim without swuEmbed. Serves the table and returns
// true for a seat (playerID 1 or 2) of a 1v1 Always Two game; false for everything else, which renders the ordinary board.
function SWUAlwaysTwoServeTable(): bool {
    $game = strval($_GET['gameName'] ?? '');
    $seat = strval($_GET['playerID'] ?? '');
    if (!preg_match('/^[0-9A-Za-z_-]{1,64}$/', $game) || !in_array($seat, ['1', '2'], true)) return false;
    $gs = @file_get_contents(__DIR__ . '/Games/' . $game . '/Gamestate.txt');
    // The game's own never-cleared flag (SWUAlwaysTwoSharedSeat): set by CreateGame for a 1v1 Always Two format and by the
    // WithAlwaysTwo fixture directive. No parse needed — this runs before NextTurn.php has loaded the engine.
    if (!is_string($gs) || strpos($gs, 'SWU_ALWAYSTWO_SHARED_SEAT') === false) return false;

    $q = $_GET;
    unset($q['swuEmbed'], $q['swuLayout'], $q['playerID']);
    // The 2×2 table (spec "The 2×2 table", owner 2026-11-20): each half opens on the matchup view against the opponent board it faces
    // — the one drawn above it. Seat 1: board 1 → P4, board 3 → P2; seat 2 mirrors it: board 2 → P3, board 4 → P1 (pairs 1↔4, 3↔2).
    $face = [1 => 4, 3 => 2, 2 => 3, 4 => 1];
    $half = function (int $board, string $mode) use ($q, $face): string {
        return 'NextTurn.php?' . http_build_query($q + ['playerID' => $board, 'swuEmbed' => $mode, 'swuLayout' => 'mobile', 'swuFace' => $face[$board]]);
    };
    $b1 = intval($seat);
    $b2 = $b1 + 2;
    $e = fn($s) => htmlspecialchars($s, ENT_QUOTES);
    header('Content-Type: text/html; charset=utf-8');
    ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Petranaki — Always Two</title>
<link rel="icon" href="/TCGEngine/SharedUI/Sites/SWUSim/assets/petranaki-favicon.png">
<style>
  :root { color-scheme: dark; }
  html, body { margin: 0; height: 100%; background: #0b0e13; }
  /* Desktop: board seat (left) | board seat+2 (right) + the log / chat / undo sidebar, which the RIGHT half carries because it is the
     table's right edge (owner, 2026-11-20: the sidebar on the right as on every other board). The two boards share the width left of
     the sidebar equally. --a2-sb must match the sidebar width GameLayoutShared.php pins in the 'a2' half. */
  :root { --a2-sb: 280px; }
  .a2-table { display: flex; gap: 4px; height: 100vh; height: 100dvh; }
  .a2-board { min-width: 0; height: 100%; border: 0; display: block; background: #0b0e13; }
  .a2-board.a2-side { flex: 0 0 calc((100% - var(--a2-sb) - 4px) / 2); }
  .a2-board.a2-main { flex: 1 1 auto; }
  /* Narrow screens: one board above the other; the page scrolls between them. */
  @media (max-width: 900px) {
    .a2-table { display: block; height: auto; }
    .a2-board { width: 100%; height: 100vh; height: 100dvh; }
    .a2-board + .a2-board { margin-top: 4px; }
  }
  /* A half that owes a decision (a prompt waiting for you) is outlined; on a phone, where one half is off-screen, a chip points to it. */
  .a2-board.a2-owes { outline: 3px solid #e2b13c; outline-offset: -3px; }
  #a2Nudge { position: fixed; left: 50%; transform: translateX(-50%); z-index: 10; display: none; padding: 8px 14px; border: 0;
             border-radius: 6px; font: 700 14px/1.2 system-ui, sans-serif; color: #1b1b1b; background: #f3d27a; cursor: pointer; }
  #a2Nudge.down { bottom: 12px; } #a2Nudge.up { top: 12px; }
  /* Several offered upgrades on one unit across the table: pick which (AlwaysTwoTable bridge). */
  #a2SubPick { position: fixed; left: 50%; top: 40%; transform: translate(-50%, -50%); z-index: 30; display: none; flex-direction: column;
               gap: 6px; padding: 12px; border-radius: 8px; background: rgb(14, 19, 27); border: 1px solid rgba(255,255,255,.2);
               font: 600 13px/1.2 system-ui, sans-serif; color: #e8e4d8; }
  #a2SubPick button { padding: 7px 14px; border: 0; border-radius: 4px; background: rgba(100,250,0,.18); color: #e8e4d8; cursor: pointer; font: inherit; }
  #a2SubPick button.a2-sub-cancel { background: rgba(255,255,255,.08); }
</style>
</head>
<body>
<div class="a2-table">
  <iframe class="a2-board a2-side" id="a2Board<?php echo $b1; ?>" title="Board <?php echo $b1; ?>" src="<?php echo $e($half($b1, 'a2side')); ?>"></iframe>
  <iframe class="a2-board a2-main" id="a2Board<?php echo $b2; ?>" title="Board <?php echo $b2; ?>" src="<?php echo $e($half($b2, 'a2')); ?>"></iframe>
</div>
<button id="a2Nudge" type="button"></button>
<script>
  // ── Crossing the table (spec "The 2×2 table" → "Crossing the table") ─────────────────────────────────────────────────────────────
  // Either of your boards may answer a choice with a card drawn in the OTHER column: board 1 attacking P2, board 3 attacking P4, a
  // "friendly unit" cost paid with the teammate board's unit, an upgrade on a unit over there. A column only sends its own board's
  // clicks, so the table bridges them (same origin, same seat key): it lights the choosing half's legal answers in the other half
  // (a2-cross-target; a unit carrying an offered upgrade gets a2-cross-subhost) and submits a click on one THROUGH the choosing half,
  // with the seat-tagged id its choice expects (swuSubmitPreviewTarget — single picks and "up to N" picks alike).
  // The choosing half's specs are authoritative (shared Sentinel, 0-HP bases …); the other half only displays them. A card that is
  // legal for the other half's OWN open choice stays with that half (own column wins).
  var A2 = { owner: [] };   // per frame index: the chooser window whose answers are marked in that frame
  function a2Frames() { return [].slice.call(document.querySelectorAll('iframe.a2-board')); }
  function a2Win(f) { try { var w = f.contentWindow; return (w && w.document) ? w : null; } catch (e) { return null; } }
  function a2Choosing(w) { var sm = w && w.SelectionMode; return !!(sm && sm.active && Array.isArray(sm._twAllSpecs)); }
  // The choosing half's legal answers, seat-tagged: legal['p2GroundArena-0'] / legal['p2GroundArena-*'], sub['p3GroundArena-0'] = [mz…].
  function a2LegalOf(w) {
    var sm = w.SelectionMode, v = w.swuView || {}, legal = {}, sub = {};
    sm._twAllSpecs.forEach(function (sp) {
      if (!sp || !sp.zone || sp.actionPayload) return;
      var z = String(sp.zone), m = /^p(\d+)(.+)$/.exec(z), seat = 0, suffix = '';
      if (m) { seat = +m[1]; suffix = m[2]; }
      else if (z.indexOf('my') === 0) { seat = +v.viewSeat; suffix = z.slice(2); }
      else if (z.indexOf('their') === 0) { seat = +v.oppSeat; suffix = z.slice(5); }
      if (!seat) return;
      var key = 'p' + seat + suffix;
      if (sp.subIndex !== null && sp.subIndex !== undefined) {
        if (sp.isSpecificCard) { var h = key + '-' + sp.specificIndex; (sub[h] = sub[h] || []).push(h + '.u' + sp.subIndex); }
        return;
      }
      legal[key + '-' + (sp.isSpecificCard ? sp.specificIndex : '*')] = true;
    });
    return { legal: legal, sub: sub, picked: (Array.isArray(sm.multiSelected) ? sm.multiSelected.slice() : []) };
  }
  // A card in a half is "(my|their)<Zone>-<i>"; its seat-tagged id uses THAT half's view (my = its board, their = the board it faces).
  function a2SeatTag(w, id) {
    var m = /^(my|their)([A-Za-z]+)-(\d+)$/.exec(id || ''); if (!m) return null;
    var v = w.swuView || {}, seat = (m[1] === 'my') ? +v.viewSeat : +v.oppSeat;
    return seat ? ('p' + seat + m[2] + '-' + m[3]) : null;
  }
  function a2Clear(w) {
    [].forEach.call(w.document.querySelectorAll('.a2-cross-target,.a2-cross-subhost,.a2-cross-picked'), function (el) {
      el.classList.remove('a2-cross-target', 'a2-cross-subhost', 'a2-cross-picked');
    });
  }
  // Mark every card in `w` that answers `chooser`'s choice. Returns the marked elements.
  function a2Mark(w, chooser, L) {
    var ownOpen = !!(w.SelectionMode && w.SelectionMode.active), out = [];
    [].forEach.call(w.document.querySelectorAll('#swuMobileRoot [id]'), function (el) {
      var mz = a2SeatTag(w, el.id); if (!mz) return;
      var zone = mz.replace(/-\d+$/, '');
      var hit = !!(L.legal[mz] || L.legal[zone + '-*']), subHit = !hit && !!L.sub[mz];
      if (ownOpen && el.classList.contains('selectable-card')) { hit = false; subHit = false; }   // own column wins
      el.classList.toggle('a2-cross-target', hit);
      el.classList.toggle('a2-cross-subhost', subHit);
      el.classList.toggle('a2-cross-picked', hit && L.picked.indexOf(mz) >= 0);
      if (hit || subHit) { el.setAttribute('data-a2mz', mz); out.push(el); }
    });
    return out;
  }
  // A unit carrying SEVERAL offered upgrades: a small list to pick one (one offered upgrade answers directly).
  function a2PickSubcard(chooser, list) {
    if (list.length === 1) { chooser.swuSubmitPreviewTarget(list[0]); return; }
    var box = document.getElementById('a2SubPick');
    if (!box) { box = document.createElement('div'); box.id = 'a2SubPick'; document.body.appendChild(box); }
    box.innerHTML = '<div class="a2-sub-title">Choose an upgrade</div>';
    list.forEach(function (mz, i) {
      var b = document.createElement('button'); b.type = 'button'; b.textContent = 'Upgrade ' + (i + 1);
      b.onclick = function () { box.style.display = 'none'; chooser.swuSubmitPreviewTarget(mz); };
      box.appendChild(b);
    });
    var c = document.createElement('button'); c.type = 'button'; c.textContent = 'Cancel'; c.className = 'a2-sub-cancel';
    c.onclick = function () { box.style.display = 'none'; }; box.appendChild(c);
    box.style.display = 'flex';
  }
  // Wire one half once per page load: a capture-phase click on a marked card goes to the choosing half, and never reaches the
  // card's own handlers (which would act for THIS board); a repaint re-marks within ~100ms.
  function a2Wire(f, i) {
    var w = a2Win(f); if (!w || w.document.__a2Wired) return;
    w.document.__a2Wired = true;
    w.document.addEventListener('click', function (e) {
      var el = e.target && e.target.closest ? e.target.closest('.a2-cross-target,.a2-cross-subhost') : null;
      var chooser = A2.owner[i];
      if (!el || !chooser || !a2Choosing(chooser)) return;
      e.stopPropagation(); e.preventDefault();
      var mz = el.getAttribute('data-a2mz');
      if (el.classList.contains('a2-cross-target')) chooser.swuSubmitPreviewTarget(mz);
      else a2PickSubcard(chooser, a2LegalOf(chooser).sub[mz] || []);
      setTimeout(a2Tick, 60);
    }, true);
    var root = w.document.getElementById('swuMobileRoot'), t = null;
    if (root && w.MutationObserver) new w.MutationObserver(function () { clearTimeout(t); t = setTimeout(a2Tick, 100); })
      .observe(root, { childList: true, subtree: true });
  }
  // One pass: wire both halves, mark each half with the OTHER half's open choice, and on a NEW choice scroll each arena to its first
  // marked card. Returns, per frame, how many of its cards answer the other half's choice (the phone chip reads it).
  window.__a2ChoiceKey = '';
  function a2Tick() {
    var fs = a2Frames(), marks = [];
    fs.forEach(a2Wire);
    fs.forEach(function (f, i) {
      var w = a2Win(f), j = 1 - i, chooser = fs[j] ? a2Win(fs[j]) : null;
      marks[i] = [];
      if (!w || !w.document.getElementById('swuMobileRoot')) return;
      if (!chooser || !a2Choosing(chooser) || typeof chooser.swuSubmitPreviewTarget !== 'function') { A2.owner[i] = null; a2Clear(w); return; }
      A2.owner[i] = chooser;
      marks[i] = a2Mark(w, chooser, a2LegalOf(chooser));
    });
    var key = fs.map(function (f, i) {
      var w = a2Win(f), sm = w && w.SelectionMode; return (sm && sm.active) ? (i + ':' + (sm.decisionIndex || 0) + ':' + (sm._twAllSpecs || []).length) : '';
    }).join('|');
    if (key !== window.__a2ChoiceKey) {
      window.__a2ChoiceKey = key;
      marks.forEach(function (els) {
        var seen = {};
        els.forEach(function (el) {
          var slot = el.closest('[id$="ArenaSlot"]'); if (!slot || seen[slot.id]) return;
          seen[slot.id] = true;
          var a = el.getBoundingClientRect(), s = slot.getBoundingClientRect();
          if (a.top < s.top || a.bottom > s.bottom) slot.scrollTop += (a.top - s.top) - 4;
        });
      });
    }
    return marks.map(function (m) { return m.length; });
  }

  // Which half owes a decision? Each half is the real board, same origin: its own pending queue is window.myDecisionQueueData, read
  // with the shared bundle's _firstPendingDecisionFromRaw. Outline that half; if it is scrolled out of view (phone), a chip points to
  // it. Otherwise, if the open choice has answers in the OTHER column and that column is off-screen, the chip points there instead.
  (function () {
    var nudge = document.getElementById('a2Nudge');
    function owes(f) {
      try { var w = f.contentWindow; return !!(w && typeof w._firstPendingDecisionFromRaw === 'function' && w._firstPendingDecisionFromRaw(w.myDecisionQueueData)); }
      catch (e) { return false; }
    }
    function offscreen(f) { var r = f.getBoundingClientRect(); return (r.top >= window.innerHeight - 40 || r.bottom <= 40) ? { below: r.top > 0 } : null; }
    function tick() {
      var counts = [];
      try { counts = a2Tick(); } catch (e) {}
      var target = null, fs = a2Frames();
      fs.forEach(function (f) {
        var o = owes(f); f.classList.toggle('a2-owes', o);
        if (!o || target) return;
        var off = offscreen(f); if (off) target = { f: f, below: off.below, text: f.title + ' needs a choice' };
      });
      if (!target) fs.forEach(function (f, i) {
        if (target || !counts[i]) return;
        var off = offscreen(f); if (off) target = { f: f, below: off.below, text: 'Targets on ' + f.title.toLowerCase() };
      });
      if (target) {
        nudge.textContent = target.text + ' ' + (target.below ? '↓' : '↑');
        nudge.className = target.below ? 'down' : 'up';
        nudge.style.display = 'block';
        nudge.onclick = function () { target.f.scrollIntoView({ behavior: 'smooth', block: 'start' }); };
      } else nudge.style.display = 'none';
    }
    setInterval(tick, 1000);
    window.addEventListener('scroll', tick, { passive: true });
  })();
  // Leaving the game from either half — Main Menu, concede-and-leave, a sideboard or room page — must take the WHOLE table there,
  // not load that page into one frame while the other half keeps polling. Same origin, so the table can read where a half went.
  (function () {
    var frames = document.querySelectorAll('iframe.a2-board');
    for (var i = 0; i < frames.length; i++) {
      frames[i].addEventListener('load', function (e) {
        var w = e.target.contentWindow, href = '';
        try { href = String(w.location.href || ''); } catch (err) { return; }
        if (!href || href === 'about:blank') return;
        if (!/\/NextTurn\.php(\?|$)/.test(w.location.pathname + (w.location.search ? '?' : ''))) window.location.href = href;
      });
    }
  })();
</script>
</body>
</html>
<?php
    return true;
}
