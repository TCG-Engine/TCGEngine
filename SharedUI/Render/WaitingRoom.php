<?php
// Shared waiting-room page renderer.
//
// Every PRIVATE lobby for a non-localMode format lands here — 2-player private games and 3-4 player
// rooms alike. Public queues keep their quick-match popup on MainMenu, because a queue wait has no
// roster, no invite and no Start button; most of this page would be inert there.
//
// WHY A PAGE AND NOT A POPUP. The popup it replaces kept its state in a JS variable (_roomCtx), so a
// refresh lost the room even though the server-side lobby was alive in apcu for another 900 seconds.
// A page addressed by ?lobby=<id> survives refresh, back and tab-close for free — and it is the entry
// point a future "Return to Lobby" needs, which makes that an anchor tag rather than a mechanism.
//
// OPT-IN is the presence of a `waitingRoom` block in the sim's SiteDef. A sim without one still HAS
// this page — the generator emits the standard pages unconditionally, because teaching it about
// per-sim features is complexity it deliberately does not carry — so the gate lives here instead:
// no config, redirect to MainMenu.
//
// Everything sim-specific comes from the LobbyAdapter named in that block (routing predicate, seat
// model, deck validation, start gate) or from blocks the sim already has (deckLibrary). That is why
// opting in is one key.
require_once __DIR__ . '/../../Database/functions.inc.php';
require_once __DIR__ . '/DeckLibrary.php';

// Returns null when the sim has not opted in. Null is a normal answer, not an error.
function WaitingRoomConfigFromSiteDef(array $def): ?array {
    $cfg = $def['waitingRoom'] ?? null;
    // A block with no adapter is a MISCONFIGURATION, not an opt-in: without it there is no routing
    // predicate and no deck validation, so the room would render and then fail later, less clearly.
    if (!is_array($cfg) || !is_string($cfg['adapter'] ?? null) || ($cfg['adapter'] ?? '') === '') return null;
    $cfg['rootName'] = $def['identity']['rootName'] ?? 'default';
    // The pre-seated state offers a deck picker driven by the sim's EXISTING deckLibrary config, so
    // opting in never restates it. Null for a sim that has no library — that state falls back to the
    // paste-a-deck-link box, which is also all a logged-out visitor gets.
    $cfg['deckLibrary'] = $def['deckLibrary'] ?? null;
    return $cfg;
}

function RenderWaitingRoom(array $def): void {
    $cfg = WaitingRoomConfigFromSiteDef($def);
    if ($cfg === null) {
        header('Location: /TCGEngine/SharedUI/MainMenu.php');
        return;
    }
    echo _WaitingRoomMarkup($cfg);
    echo _WaitingRoomScript($cfg);
}

// The saved-deck dropdown for the pre-seated state, or '' for a guest / a sim with no library.
// Guests are deliberately supported: JoinQueue lets someone join a private invite without an account,
// and they get the paste-a-deck-link box instead.
function _WaitingRoomDeckLibrary(array $cfg): string {
    if (!is_array($cfg['deckLibrary'] ?? null)) return '';
    $uid = intval($_SESSION['userid'] ?? 0);
    if ($uid <= 0) return '';
    if (!function_exists('RenderDeckLibrary')) return '';
    $lib = DeckLibraryConfigFromSiteDef(['deckLibrary' => $cfg['deckLibrary'],
                                         'identity'    => ['rootName' => $cfg['rootName']]]);
    return RenderDeckLibrary($uid, $lib);
}

// The shell. Everything inside #wr-root is drawn by the script from the poll payload — the server
// renders no lobby state, because the page has to re-render on every poll anyway.
function _WaitingRoomStyles(): string {
    return <<<'CSS'
<style>
/* The panel is deliberately wide: this page's job is comparing four decks side by side, and the
   default card width made a 3-card identity strip feel cramped at half a column. */
.wr-panel { max-width: min(1600px, 96vw); margin: 0 auto; padding: 24px; border-radius: 12px; }

/* ALWAYS 2x2 — for 2-seat lobbies and 3-4 seat rooms alike.
   ⚠ NOT auto-fit. auto-fit divides the available width by the track floor, so on a wide panel a
   4-seat room silently becomes 1x4 rather than 2x2; it measured THREE columns at 900px before this.
   An explicit 2-track grid plus one media query is the only way to actually mean "2x2". */
.wr-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
@media (max-width: 760px) { .wr-grid { grid-template-columns: 1fr; } }

.wr-seat { border: 1px solid #ffffff1f; border-radius: 8px; padding: 12px; min-height: 110px; }
.wr-seat-empty { color: #aab6c4; }
.wr-seat-label { font-size: 14px; font-weight: 600; letter-spacing: .03em; color: #9fadbd; }
/* YOUR seat, marked with an INSET ring rather than a border or background: the team columns already
   set both of those inline (red/blue accents), so anything else here would be overridden in Team Suns
   or would fight the team tint. An inset shadow is untouched by either. */
.wr-seat-mine { box-shadow: inset 0 0 0 2px var(--success, #6fcf97); }
.wr-pill { display: inline-block; font-size: 11px; font-weight: 600; letter-spacing: .04em;
           padding: 2px 8px; border-radius: 999px; border: 1px solid currentColor; }
.wr-pill-ready    { color: #6fcf97; }
.wr-pill-notready { color: #d9a441; }
.wr-pill-baddeck  { color: #ff6b6b; }
.wr-seat-foot { display: flex; align-items: center; gap: 8px; margin-top: 6px; flex-wrap: wrap; }
/* An EXPLICIT shared height, not align-items:stretch. The input and the button have different
   intrinsic heights (the input carries a 1px border; the button carries none and draws its edge with
   absolutely-positioned pseudo-elements), so centring left the button 2px short and its midline 5px
   low — and switching to stretch was worse, blowing the button up to 59px against the input's 29.
   ⚠ Flex-stretch for equal heights is a documented trap in this codebase; a height chain is the
   reliable form and behaves the same in Chromium, Firefox and WebKit. */
.wr-deckbar { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.wr-deckbar textarea,
.wr-deckbar .btn { height: 34px; box-sizing: border-box; line-height: 1; }
/* Ready sits beside the deck button, so it is part of this row's height chain too. */
#wr-ready-slot { display: inline-flex; align-items: center; flex: 0 0 auto; }
/* ⚠ margin:0 is the actual fix. A theme rule gives text inputs 10px top / 20px BOTTOM margin, so the
   flex line grew to the input's 64px margin box and centring the 34px button inside it put the button
   5px low — the misalignment looked like a height problem and was not. */
.wr-deckbar textarea { flex: 1 1 260px; min-width: 0; margin: 0; padding: 7px 10px; font-size: 15px;
                       resize: vertical; font-family: inherit; white-space: pre; overflow-x: auto; }
.wr-deckbar .btn { flex: 0 0 auto; display: inline-flex; align-items: center; }

/* Leave sits away from the two buttons that COMMIT something, so the destructive action is never
   next to Start. Ready is yours (bottom left); Start is the host's (bottom right). */
.wr-head { display: flex; align-items: center; justify-content: space-between; gap: 12px;
           flex-wrap: wrap; margin-bottom: 12px; }

/* The action block: the left column STACKS (deck link, then Ready) while Start sits to the right,
   vertically CENTRED across both rows.
   ⚠ Centred, not stretched. Filling the column's height made Start a ~106px slab — visually louder
   than the whole rest of the panel. It stays prominent through padding and type size instead, which
   scales with the button rather than with however tall the left column happens to be. */
.wr-actions { display: flex; gap: 16px; align-items: stretch; margin: 16px 0 4px; }
.wr-actions-main { flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column;
                   justify-content: center; gap: 12px; }
#wr-actions-right { display: flex; flex-direction: column; justify-content: center; flex: 0 0 auto; }
/* The deck bar is a secondary control — at full width it dominated the action block and competed
   with Start. Half the panel is plenty for a deck URL and keeps the row visually subordinate.
   ⚠ The percentage resolves against .wr-actions-main (the LEFT COLUMN), not the panel, and the
   column is the panel minus Start and the gap — so a literal 50% here measures ~44% of the panel.
   57% of the column lands on ~50% of the panel across the widths this page actually renders at. */
#wr-deck { max-width: 57%; }
@media (max-width: 760px) {
  .wr-actions { flex-direction: column; align-items: stretch; }
  #wr-deck { max-width: none; }   /* stacked layout needs the full width for a deck URL */
}

/* The status box sits under everything and answers "who is here, and what are we waiting for". */
.wr-status { display: flex; align-items: center; gap: 12px; margin-top: 12px; padding: 10px 14px;
             font-size: 13px; color: #aab6c4; border: 1px solid #ffffff14; border-radius: 8px;
             background: #ffffff08; }
.wr-count { display: inline-flex; align-items: center; gap: 6px; flex: none;
            font-weight: 600; font-size: 14px; color: var(--text, #f2ead7); }
/* people-icon.png is a solid BLACK glyph, so it needs inverting to read on a dark panel — the same
   treatment the menu gives refresh.svg. Without it the icon is an invisible smudge. */
.wr-count-icon { filter: invert(100%); opacity: .75; display: block; }

/* Start is the page's terminal action, so it is the biggest thing on it. --btn-pad is the shared
   button system's own sizing knob, so this scales the real component rather than fighting it. */
.wr-btn-start { --btn-pad: 13px 32px; font-size: 17px; letter-spacing: .05em; }
.wr-seat-who   { font-size: 15px; margin: 2px 0 4px; }

/* Tiles scale with the panel. aspect-ratio derives the height from the real 628x450 landscape card,
   so leaders and bases share one baseline without hardcoding a second number that can drift. */
.wr-strip { display: flex; flex-wrap: wrap; align-items: flex-start; gap: 6px; margin: 6px 0; }
/* The ring lives on a WRAPPER, not on the img's own border: a border can only be one colour, and a
   dual-aspect leader needs two halves. The wrapper's background is a hard-stop gradient and its
   padding is the ring width, so N aspects split into N equal segments with no extra elements. */
.wr-cardwrap { display: block; flex: none; padding: 3px; border-radius: 7px; line-height: 0; }
.wr-card  { width: clamp(96px, 9vw, 172px); aspect-ratio: 628 / 450; box-sizing: border-box;
            object-fit: cover; object-position: center top; display: block; border-radius: 4px; }

/* Team columns: two side by side, wrapping to one on a narrow screen. */
.wr-teams   { display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-start; }
.wr-teamcol { flex: 1 1 340px; min-width: 0; }
.wr-teamhdr { font-weight: bold; margin-bottom: 8px; }
.wr-teamcol .wr-seat { margin-bottom: 10px; }
</style>
CSS;
}

function _WaitingRoomMarkup(array $cfg): string {
    $root = htmlspecialchars((string)$cfg['rootName'], ENT_QUOTES);
    $lib  = _WaitingRoomDeckLibrary($cfg);
    $libBlock = $lib === '' ? '' :
        '<div id="wr-deck-library" style="margin-bottom:8px;">' . $lib . '</div>';
    $css = _WaitingRoomStyles();
    return <<<HTML
{$css}
<div class="row-wrapper">
  <div class="card ga-glass-card wr-panel" style="color:var(--text);">
    <div id="wr-root" data-root-name="{$root}" data-state="loading">
      <div class="wr-head">
        <h2 id="wr-title" style="margin:0;">Lobby</h2>
        <div id="wr-head-actions"></div>
      </div>
      <div id="wr-state" style="margin-bottom:12px;"></div>
      <div id="wr-invite" style="margin-bottom:16px;"></div>
      <div id="wr-roster" style="margin-bottom:16px;"></div>
      <div class="wr-actions">
        <div class="wr-actions-main">
      <div id="wr-deck" style="display:none;">
        {$libBlock}
        <div class="wr-deckbar">
          <textarea id="wr-deck-input" rows="1" spellcheck="false"
                    placeholder="Paste a deck link or deck list"></textarea>
          <button id="wr-deck-btn" type="button" class="btn">Use this deck</button>
          <span id="wr-ready-slot"></span>
        </div>
        <div id="wr-deck-msg" style="font-size:12px;margin-top:6px;"></div>
      </div>
          <div id="wr-actions-left"></div>
        </div>
        <div id="wr-actions-right"></div>
      </div>
      <div id="wr-status" class="wr-status">
        <span class="wr-count" title="Players in this lobby">
          <img class="wr-count-icon" src="/TCGEngine/Assets/Icons/people-icon.png" alt="Players" width="18" height="18">
          <span id="wr-count-n">-</span>
        </span>
        <span id="wr-hint"></span>
      </div>
    </div>
  </div>
</div>
HTML;
}

// The page's whole behaviour. A NOWDOC (<<<'JS') so PHP never interpolates JavaScript $variables;
// the only server-injected values are prepended as JSON above it.
function _WaitingRoomScript(array $cfg): string {
    $boot = 'window.WR_ROOT_NAME = ' . json_encode((string)$cfg['rootName']) . ';';
    $body = <<<'JS'
(function () {
  'use strict';

  // The authKey IS the seat — hash_equals on it authorises changing a deck or leaving. It is NEVER
  // put in the URL (history, referrers, screenshots) and never in a cookie (the server reads it from
  // a POST body, so a cookie buys nothing and costs CSRF surface). localStorage survives tab close,
  // which "only Leave releases the seat" requires.
  var KEY_PREFIX = 'tcg:lobbyAuth:';
  var LOBBY_TTL_MS = 900000;          // matches the apcu TTL the lobby is stored with
  var POLL_MS = 1500;

  var ROOT = window.WR_ROOT_NAME;
  var lobbyID = '', inviteCode = '', myPlayerID = 0, pollTimer = null, navigating = false;
  // Signature of what the roster last DREW. The poll fires every 1.5s and rendering rebuilds
  // #wr-roster with innerHTML, which destroys and recreates every <img> — so an idle lobby flickered
  // its card art once a second as the fresh elements repainted. Re-render only when something the
  // roster actually shows has changed.
  var lastSig = '';

  function appBase() { var p = location.pathname, i = p.indexOf('/TCGEngine/'); return i >= 0 ? p.slice(0, i + 11) : '/TCGEngine/'; }
  function qs(k) { return new URLSearchParams(location.search).get(k) || ''; }
  function el(id) { return document.getElementById(id); }
  function esc(t) { return String(t == null ? '' : t).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }

  function loadKey(l) {
    try {
      var v = JSON.parse(localStorage.getItem(KEY_PREFIX + l) || 'null');
      if (!v || !v.authKey) return '';
      if (Date.now() - (v.ts || 0) > LOBBY_TTL_MS) { clearKey(l); return ''; }
      return v.authKey;
    } catch (e) { return ''; }
  }
  function saveKey(l, k) { try { localStorage.setItem(KEY_PREFIX + l, JSON.stringify({ authKey: k, ts: Date.now() })); } catch (e) {} }
  function clearKey(l) { try { localStorage.removeItem(KEY_PREFIX + l); } catch (e) {} }

  function setState(s) { var r = el('wr-root'); if (r) r.setAttribute('data-state', s); }

  function post(path, params, cb) {
    var x = new XMLHttpRequest();
    x.open('POST', appBase() + path, true);
    x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    x.onload = function () { var r = {}; try { r = JSON.parse(x.responseText); } catch (e) {} cb(r); };
    x.onerror = function () { cb({}); };
    x.send(params);
  }

  // ── Rendering ──────────────────────────────────────────────────────────────────────────────────
  // One identity thumbnail. A FIXED 64x46 box, never height:auto — leaders and bases are landscape
  // (628x450) so nothing real is cropped, but the box is what keeps the strip on one baseline if a
  // portrait card ever reaches it. The url is resolved SERVER-side by the sim's LobbyAdapter.
  // The identity ring, built from the card's colour list. ONE colour paints a smooth ring; several
  // split it into equal segments with hard stops (left-to-right), so a dual-aspect leader reads as
  // two halves and a single- or repeated-aspect one (DJ: Cunning,Cunning) stays smooth.
  // The page does not know what an "aspect" is — it just draws the colours the adapter sent.
  function ring(colors) {
    var cs = (colors && colors.length) ? colors : ['#4b5b6d'];
    if (cs.length === 1) return cs[0];
    var stops = cs.map(function (c, i) {
      return c + ' ' + (i * 100 / cs.length).toFixed(4) + '% ' + ((i + 1) * 100 / cs.length).toFixed(4) + '%';
    });
    return 'linear-gradient(90deg,' + stops.join(',') + ')';
  }
  function thumb(c) {
    // Geometry lives in the stylesheet so tiles scale with the panel; only the ring varies per card.
    return '<span class="wr-cardwrap" style="background:' + ring(c.colors) + ';">' +
           '<img class="wr-card' + (c.kind === 'base' ? ' wr-card-base' : '') + '"' +
           ' src="' + esc(c.url) + '" alt="' + esc(c.name) + '" title="' + esc(c.name) + '"' +
           ' decoding="async" onerror="this.parentNode.style.display=\'none\';">' +
           '</span>';
  }
  function strip(entry) {
    var cards = (entry && entry.identity && entry.identity.cards) || [];
    if (!cards.length) return '';
    return '<div class="wr-strip">' + cards.map(thumb).join('') + '</div>';
  }
  function seatBody(entry, seatNo) {
    // No "(you)" here — the green ring on your own tile already says it, and repeating it in text
    // was two signals for one fact. It IS kept in the unassigned holding line below, where there is
    // no tile and therefore no ring to read it from.
    var who = 'P' + entry.playerID + (entry.isHost ? ' (host)' : '');
    // deckOk and ready are DIFFERENT facts: a legal deck you are still swapping is not a deck you are
    // ready to play. Showing one pill for both would hide exactly the state this room exists to carry.
    var pill = !entry.deckOk ? '<span class="wr-pill wr-pill-baddeck">NO DECK</span>'
             : entry.ready   ? '<span class="wr-pill wr-pill-ready">READY</span>'
                             : '<span class="wr-pill wr-pill-notready">NOT READY</span>';
    var deck = entry.deckOk ? '<span style="color:#6fcf97;font-size:12px;">deck ✓</span>'
                            : '<span style="color:#ff6b6b;font-size:12px;">deck missing/invalid</span>';
    return '<div class="wr-seat-label">Seat ' + seatNo + '</div>' +
           '<div class="wr-seat-who">' + esc(who) + '</div>' + strip(entry) +
           '<div class="wr-seat-foot">' + pill + deck + '</div>';
  }
  function emptySeat(sn, inner) {
    return '<div class="wr-seat-label">Seat ' + sn + '</div><div style="font-size:13px;">' + inner + '</div>';
  }

  function renderRoster(d) {
    var host = el('wr-roster'); if (!host) return;
    var roster = d.roster || [];
    var model = d.seatModel || { maxPlayers: d.maxPlayers || 2, teams: null };
    var bySeat = {}, byId = {};
    roster.forEach(function (r) { if (r.seat) bySeat[r.seat] = r; byId[r.playerID] = r; });

    // TEAMS: two columns. Red holds seats 1,3; blue holds 2,4.
    //
    // ⚠ A seat is NULL until its player picks a team, so an unassigned player belongs to no column.
    // They are listed underneath instead — without that, the room's own creator sees four "open"
    // seats and no sign of themselves, which is exactly how this first shipped broken.
    if (model.teams) {
      var myTeam = null;
      roster.forEach(function (r) { if (r.playerID === myPlayerID) myTeam = r.team; });

      var cols = model.teams.map(function (team, ti) {
        var slots = ti === 0 ? [1, 3] : [2, 4];
        var accent = ti === 0 ? '#c0392b' : '#2d6fa8';
        var occupied = slots.filter(function (sn) { return bySeat[sn]; }).length;
        var cells = slots.map(function (sn) {
          var e = bySeat[sn];
          if (e) {
            return '<div class="wr-seat' + (e.playerID === myPlayerID ? ' wr-seat-mine' : '') +
                   '" data-seat="' + sn + '" style="border-color:' + accent +
                   '55;background:' + accent + '18;">' + seatBody(e, sn) + '</div>';
          }
          // Joining picks the TEAM; the server assigns the lowest free seat of the pair, so the
          // button is per-team even though it is drawn per-slot.
          var full = occupied >= slots.length;
          var mine = (myTeam === team);
          var label = (full || mine)
            ? '<div style="font-size:13px;">Open</div>'
            : '<button class="btn wr-join-team" type="button" data-team="' + esc(team) + '" style="font-size:12px;">Join ' +
              team.charAt(0).toUpperCase() + team.slice(1) + '</button>';
          return '<div class="wr-seat wr-seat-empty" data-seat="' + sn + '" style="border-color:' + accent + '55;">' +
                 emptySeat(sn, label) + '</div>';
        }).join('');
        return '<div class="wr-teamcol"><div class="wr-teamhdr" style="color:' + accent + ';">' +
               team.charAt(0).toUpperCase() + team.slice(1) + ' (' + occupied + '/' + slots.length + ')</div>' + cells + '</div>';
      }).join('');

      var unassigned = roster.filter(function (r) { return !r.seat; });
      var holding = unassigned.length
        ? '<div style="margin-top:4px;font-size:12px;color:#aab6c4;">Not on a team yet: ' +
          unassigned.map(function (r) {
            return esc('P' + r.playerID + (r.playerID === myPlayerID ? ' (you)' : ''));
          }).join(', ') + '</div>'
        : '';

      host.innerHTML = '<div class="wr-teams">' + cols + '</div>' + holding;
      Array.prototype.forEach.call(host.querySelectorAll('.wr-join-team'), function (btn) {
        btn.onclick = function () { doSetTeam(btn.getAttribute('data-team')); };
      });
      return;
    }

    // FLAT (2-seat private lobbies AND Twin Suns): the same 2x2 grid shape as the team table, minus
    // the team headers and the picker. Geometry is .wr-grid in the stylesheet — an explicit 2-track
    // grid, so a wide panel cannot turn a 4-seat room into 1x4.
    // Match on playerID — .seat is null outside team games.
    var rows = [];
    for (var i = 1; i <= (model.maxPlayers || 2); i++) {
      var e = byId[i];
      rows.push('<div class="wr-seat' + (e ? (e.playerID === myPlayerID ? ' wr-seat-mine' : '') : ' wr-seat-empty') +
                '" data-seat="' + i + '">' +
                (e ? seatBody(e, i) : emptySeat(i, 'Waiting…')) + '</div>');
    }
    host.innerHTML = '<div class="wr-grid">' + rows.join('') + '</div>';
  }

  function renderInvite(d) {
    var host = el('wr-invite'); if (!host) return;
    if (!d.inviteCode) { host.innerHTML = ''; return; }
    var link = location.origin + appBase() + 'SharedUI/WaitingRoom.php?invite=' + encodeURIComponent(d.inviteCode);
    host.innerHTML = 'Invite: <strong>' + esc(d.inviteCode) + '</strong> ' +
                     '<button id="wr-copy" type="button" class="btn" style="margin-left:8px;">Copy Invite Link</button>';
    el('wr-copy').onclick = function () {
      var b = el('wr-copy');
      // Never a native dialog — StyledDialog.js loads on every SiteDef site (Head.php) and provides
      // themed Toast/StyledPrompt. A window.prompt here would be an unthemed OS box mid-flow.
      // The fallback is a PROMPT rather than an alert on purpose: its input is selectable, so someone
      // whose clipboard was blocked can still copy the link by hand.
      var manual = function () {
        if (typeof StyledPrompt === 'function') {
          StyledPrompt('Your browser blocked the clipboard. Copy this invite link:',
                       { title: 'Invite Link', initial: link, confirmLabel: 'Done' });
        }
      };
      var copied = function () {
        b.textContent = 'Copied!';
        setTimeout(function () { b.textContent = 'Copy Invite Link'; }, 1200);
        if (typeof Toast === 'function') Toast('Invite link copied.', { type: 'success' });
      };
      // WebKit needs the write inside the click turn, so nothing async may run before writeText.
      try {
        if (!navigator.clipboard || !navigator.clipboard.writeText) { manual(); return; }
        navigator.clipboard.writeText(link).then(copied, manual);
      } catch (e) { manual(); }
    };
  }

  function renderControls(d, seated) {
    var head = el('wr-head-actions'), left = el('wr-actions-left'), right = el('wr-actions-right');
    if (!head || !left || !right) return;
    var me = (d.roster || []).filter(function (r) { return r.playerID === myPlayerID; })[0];
    var isHost = !!(me && me.isHost);
    var full = (d.numPlayers || 0) >= ((d.seatModel && d.seatModel.maxPlayers) || d.maxPlayers || 2);

    if (!seated) {
      // Nothing to leave yet, Start is not yours, and joining is ALREADY the deck bar's button —
      // a second "Join" beside it was two controls for one action. The deck bar keeps it, because
      // that is where the deck you are joining with is chosen.
      head.innerHTML = '';
      right.innerHTML = '';
      left.innerHTML = '';
      el('wr-ready-slot').innerHTML = '';   // nothing to ready until you hold a seat
      var jb = el('wr-deck-btn');
      if (jb) jb.disabled = full;
      el('wr-hint').textContent = full ? 'Lobby is full.' : 'Paste or pick a deck to join this lobby.';
      return;
    }
    var blockers = d.blockers || [];
    var amReady = !!(me && me.ready);
    // Leave sits in the HEADER, deliberately far from Start: the destructive action should not be
    // adjacent to the one everyone is waiting on. Ready is yours (bottom left); Start is the host's
    // (bottom right). Both are always rendered so it is obvious which one belongs to you.
    head.innerHTML  = '<button id="wr-leave" type="button" class="btn">Leave</button>';
    // Ready lives NEXT TO the deck button, not on its own row: loading a deck auto-readies you, so
    // the two are one thought — "this is my deck, and I'm good to go". #wr-actions-left stays empty
    // here and is reserved for the GONE state's way out.
    left.innerHTML = '';
    el('wr-ready-slot').innerHTML =
      '<button id="wr-ready" type="button" class="btn"' + (me && me.deckOk ? '' : ' disabled') + '>' +
      (amReady ? 'Unready' : 'Ready') + '</button>';
    // Only the HOST can ever start, so only the host sees the button at all — a permanently disabled
    // control is noise for everyone else, and the hint line already tells them what is being waited on.
    // The host DOES keep seeing it while blocked: disabled-with-a-reason is the feedback that tells
    // them why they cannot start yet.
    // ⚠ The success skin is applied only while Start is LIVE. .btn-success wires the theme's
    // --success / --success-surface tokens, and .btn:disabled only dims (opacity .42, saturate .4) —
    // so keeping the class on a disabled button yields a washed-out GREEN, not the neutral grey a
    // disabled control should be. Dropping the class falls back to the default .btn skin.
    var canStart = isHost && !blockers.length;
    right.innerHTML = isHost
      ? '<button id="wr-start" type="button" class="btn wr-btn-start' +
          (canStart ? ' btn-success' : '') + '"' + (canStart ? '' : ' disabled') + '>Start Game</button>'
      : '';
    el('wr-hint').textContent = blockers.length
      ? blockers.join('; ')
      : (isHost ? 'Everyone is ready — press Start.' : 'Everyone is ready — waiting for the host.');
    el('wr-ready').onclick = function () { doSetReady(!amReady); };
    if (el('wr-start')) el('wr-start').onclick = doStart;   // absent for non-hosts
    el('wr-leave').onclick = doLeave;
  }

  function render(d) {
    var seated = !!(myPlayerID && (d.roster || []).some(function (r) { return r.playerID === myPlayerID; }));
    setState(seated ? 'seated' : 'notseated');
    el('wr-title').textContent = (d.seatModel && d.seatModel.teams) ? 'Team Room'
                               : (((d.seatModel && d.seatModel.maxPlayers) || 2) > 2 ? 'Room' : 'Private Lobby');
    el('wr-state').textContent = '';
    // Seats occupied vs seats available — the one number everyone in a lobby keeps checking.
    el('wr-status').style.display = '';
    el('wr-count-n').textContent = (d.numPlayers || 0) + '/' +
                                   ((d.seatModel && d.seatModel.maxPlayers) || d.maxPlayers || 2);
    // The deck panel stays available while seated — changing decks in the lobby is the point.
    el('wr-deck').style.display = '';
    var db = el('wr-deck-btn');
    if (db) {
      db.textContent = seated ? 'Change deck' : 'Join with this deck';
      if (seated) db.disabled = false;   // the not-seated branch disables it when the lobby is full
    }
    renderInvite(d);
    renderRoster(d);
    renderControls(d, seated);
  }

  function renderGone(msg) {
    setState('gone');
    if (lobbyID) clearKey(lobbyID);
    el('wr-state').innerHTML = '<div style="color:#ff6b6b;">' + esc(msg || 'This lobby has ended.') + '</div>';
    el('wr-invite').innerHTML = '';
    el('wr-roster').innerHTML = '';
    el('wr-deck').style.display = 'none';
    el('wr-head-actions').innerHTML = '';
    el('wr-actions-right').innerHTML = '';
    el('wr-actions-left').innerHTML =
      '<a href="' + appBase() + 'SharedUI/MainMenu.php"><button type="button" class="btn">Back to menu</button></a>';
    el('wr-hint').textContent = '';
    el('wr-ready-slot').innerHTML = '';
    el('wr-status').style.display = 'none';   // no lobby, so no seat count to report
  }

  // ── Actions ────────────────────────────────────────────────────────────────────────────────────
  // The chosen deck: a saved-library selection wins over the paste box, since picking from the list
  // is the more deliberate act.
  function chosenDeck() {
    var sel = document.querySelector('#wr-deck-library .dl-select');
    if (sel && sel.selectedIndex > 0) {
      var o = sel.options[sel.selectedIndex];
      var v = o.getAttribute('data-queue-input') || o.getAttribute('data-id') || '';
      if (v) return v;
    }
    var inp = el('wr-deck-input');
    return inp ? inp.value.trim() : '';
  }

  function doJoin() {
    var deck = chosenDeck();
    if (!deck) { el('wr-deck-msg').style.color = '#ff6b6b'; el('wr-deck-msg').textContent = 'Choose or paste a deck first.'; return; }
    // The deck bar's own button IS the join control — there is no separate #wr-join. Look it up
    // rather than assuming: this function also runs from the button's click handler.
    var btn = el('wr-deck-btn');
    if (btn) btn.disabled = true;
    post('APIs/Lobbies/JoinQueue.php',
      'rootName=' + encodeURIComponent(ROOT) + '&privateInviteCode=' + encodeURIComponent(inviteCode) +
      '&deckLink=' + encodeURIComponent(deck) + '&preconstructedDeck=&game_type=',
      function (r) {
        if (!r.success) {
          if (btn) btn.disabled = false;
          el('wr-deck-msg').style.color = '#ff6b6b';
          el('wr-deck-msg').textContent = r.message || 'Unable to join.';
          return;
        }
        if (r.lobbyID) lobbyID = r.lobbyID;
        myPlayerID = r.playerID || 0;
        if (r.authKey) saveKey(lobbyID, r.authKey);
        rewriteUrl();
        poll();
      });
  }

  function doLeave() {
    var key = loadKey(lobbyID);
    if (pollTimer) { clearTimeout(pollTimer); pollTimer = null; }
    navigating = true;
    post('APIs/Lobbies/LeaveQueue.php',
      'rootName=' + encodeURIComponent(ROOT) + '&lobbyID=' + encodeURIComponent(lobbyID) +
      '&playerID=' + encodeURIComponent(myPlayerID) + '&authKey=' + encodeURIComponent(key),
      function () { clearKey(lobbyID); location.href = appBase() + 'SharedUI/MainMenu.php'; });
  }

  function doStart() {
    var sb = el('wr-start');
    if (sb) sb.disabled = true;
    post('APIs/Lobbies/StartRoom.php',
      'rootName=' + encodeURIComponent(ROOT) + '&lobbyID=' + encodeURIComponent(lobbyID) +
      '&playerID=' + encodeURIComponent(myPlayerID) + '&authKey=' + encodeURIComponent(loadKey(lobbyID)),
      function (r) {
        if (!r.success) {
          if (sb) sb.disabled = false;
          el('wr-hint').textContent = r.message || 'Could not start.';
        }
        // On success the poll sees gameName and navigates; nothing to do here.
      });
  }

  // Team Suns: pick a team. The server assigns the lowest free seat of that team's pair and
  // renumbers nothing — the next poll re-renders from the server, so there is no optimistic local
  // mutation to get out of step.
  function doSetTeam(team) {
    post('APIs/Lobbies/SetTeam.php',
      'lobbyID=' + encodeURIComponent(lobbyID) + '&playerID=' + encodeURIComponent(myPlayerID) +
      '&authKey=' + encodeURIComponent(loadKey(lobbyID)) + '&team=' + encodeURIComponent(team),
      function (r) { if (!r.success) el('wr-hint').textContent = r.message || 'Could not change team.'; });
  }

  // Ready is sent as an explicit value rather than a bare toggle, so a double-click cannot leave the
  // button and the seat disagreeing.
  function doSetReady(want) {
    var b = el('wr-ready'); if (b) b.disabled = true;
    post('APIs/Lobbies/SetReady.php',
      'lobbyID=' + encodeURIComponent(lobbyID) + '&authKey=' + encodeURIComponent(loadKey(lobbyID)) +
      '&ready=' + (want ? '1' : '0'),
      function (r) {
        if (!r.success) { el('wr-hint').textContent = r.message || 'Could not change ready state.'; if (b) b.disabled = false; }
        lastSig = '';   // force the next poll to redraw the pill and the button
      });
  }

  function doChangeDeck() {
    var deck = chosenDeck();
    if (!deck) return;
    post('APIs/Lobbies/UpdateLobbyDeck.php',
      'lobbyID=' + encodeURIComponent(lobbyID) + '&playerID=' + encodeURIComponent(myPlayerID) +
      '&authKey=' + encodeURIComponent(loadKey(lobbyID)) + '&deckLink=' + encodeURIComponent(deck),
      function (r) {
        var m = el('wr-deck-msg');
        m.style.color = r.deckOk ? '#9ed9b4' : '#ff6b6b';
        m.textContent = r.deckOk ? 'Deck accepted — you are ready.' : (r.message || 'Deck rejected.');
        lastSig = '';   // the roster's identity strip and ready pill both just changed
      });
  }

  // ── Polling ────────────────────────────────────────────────────────────────────────────────────
  function rewriteUrl() {
    if (!lobbyID) return;
    try { history.replaceState(null, '', location.pathname + '?lobby=' + encodeURIComponent(lobbyID)); } catch (e) {}
  }

  function poll() {
    if (navigating) return;
    var key = loadKey(lobbyID);
    var params = 'rootName=' + encodeURIComponent(ROOT) +
                 '&lobbyID=' + encodeURIComponent(lobbyID) +
                 '&inviteCode=' + encodeURIComponent(inviteCode) +
                 '&playerID=' + encodeURIComponent(myPlayerID) +
                 '&authKey=' + encodeURIComponent(key);
    post('APIs/Lobbies/PollLobbyUpdates.php', params, function (r) {
      if (navigating) return;
      if (r.gone) { renderGone(r.message); return; }

      if (r.lobbyID && r.lobbyID !== lobbyID) { lobbyID = r.lobbyID; rewriteUrl(); }

      if (r.started && r.gameName) {
        // No match-found countdown: that popup celebrates an UNEXPECTED queue pairing. Here the host
        // pressed Start and everyone watched it happen.
        navigating = true;
        setState('started');
        el('wr-state').textContent = 'Starting…';
        el('wr-head-actions').innerHTML = '';
        el('wr-actions-left').innerHTML = '';
        el('wr-actions-right').innerHTML = '';
        el('wr-status').style.display = 'none';
        var seat = r.playerID || myPlayerID;
        var k = loadKey(lobbyID);
        if (k && ['1','2','3','4'].indexOf(String(seat)) >= 0) {
          try { document.cookie = 'lastAuthKey=' + encodeURIComponent(k) + '; max-age=' + (30*24*60*60) + '; path=/; SameSite=Lax'; } catch (e) {}
        }
        location.href = appBase() + 'NextTurn.php?gameName=' + encodeURIComponent(r.gameName) +
                        '&playerID=' + encodeURIComponent(seat) + '&folderPath=' + encodeURIComponent(ROOT);
        return;
      }

      if (r.success && r.isRoom) {
        // ⚠ ADOPT the seat the server reports. StartRoom renumbers seats (team rooms sort by picked
        // seat), so a captured playerID goes stale and the game rejects the browser as
        // "not authenticated as player N" — which is exactly what broke every non-host seat once.
        if (r.playerID) myPlayerID = r.playerID;
        // Only the fields the roster renders go into the signature; myPlayerID is in it because
        // the own-seat ring and the Join/Start controls depend on which seat we are.
        var sig = JSON.stringify([r.roster, r.seatModel, r.blockers, r.numPlayers, r.inviteCode, myPlayerID]);
        if (sig !== lastSig) { lastSig = sig; render(r); }
      }
      pollTimer = setTimeout(poll, POLL_MS);
    });
  }

  function boot() {
    lobbyID = qs('lobby');
    inviteCode = qs('invite');
    if (!lobbyID && !inviteCode) { renderGone('No lobby specified.'); return; }
    myPlayerID = 0;
    var btn = el('wr-deck-btn');
    if (btn) btn.onclick = function () { if (el('wr-root').getAttribute('data-state') === 'seated') doChangeDeck(); else doJoin(); };
    if (lobbyID) rewriteUrl();
    poll();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
JS;
    return "<script>\n" . $boot . "\n" . $body . "\n</script>\n";
}
