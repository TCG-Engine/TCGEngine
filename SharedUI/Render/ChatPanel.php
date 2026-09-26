<?php
// A self-contained chat (and optionally game-log) panel for pages that are NOT the game board.
//
// ⚠ WHY THIS IS NOT THE IN-GAME SIDEBAR. That one is welded to the gamestate poll and lives inside
// GameLayoutShared.php's ~4,000-line JS blob, while the Waiting Room is sim-agnostic and the
// Sideboard is a standalone page with its own CSS variables and no Core Card() renderer. A shared
// partial is the smaller unit and the only one that can be tested on its own.
//
// ⚠ THE SCOPE ARRIVES FROM JS, NOT FROM PHP. The Waiting Room learns its lobbyID and authKey only
// after its poll reads them from localStorage, so the panel ships inert and the page calls
// TCGChatPanel.attach(). The Sideboard knows both at render time and attaches immediately.
//
// Palette and autoscroll deliberately match the in-game log (see
// SWUSim/Tests/Visual/GameLogAndChat_MergedSidebarStream.md) so players meet one visual language.

function RenderChatPanel(array $opts = []): string {
    $mode  = ($opts['mode'] ?? 'chat') === 'chat+log' ? 'chat+log' : 'chat';
    $title = htmlspecialchars(strval($opts['title'] ?? 'CHAT'), ENT_QUOTES);
    $css   = _ChatPanelStyles();
    $js    = _ChatPanelScript();
    return <<<HTML
{$css}
<?php /* ⚠ class="btn" IS REQUIRED. SharedUI/Render/Tests/RunRenderTests.php refuses any rendered
         button that leans on components.css's bare-element alias instead of the .btn COMPONENT —
         same tokens, but the component is the supported path and wires the chamfer pseudos. The
         id-scoped rules below still win on specificity for size and position. */ ?>
<button id="tcg-chat-toggle" class="btn" type="button" aria-label="Chat" aria-expanded="false" data-ready="0">&#128172;</button>
<aside id="tcg-chat-panel" data-mode="{$mode}" data-open="0" data-ready="0">
  <div class="tcgc-label">{$title}</div>
  <div id="tcgc-stream" role="log" aria-live="polite"></div>
  <div id="tcgc-composer">
    <input id="tcgc-input" type="text" maxlength="500" placeholder="Say something" autocomplete="off">
    <button id="tcgc-send" type="button" class="btn">Send</button>
  </div>
  <div id="tcgc-note"></div>
</aside>
{$js}
HTML;
}

function _ChatPanelStyles(): string {
    return <<<'CSS'
<style>
/* 18% of the viewport, clamped. The owner asked for 15-20%; the floor stops it becoming unreadable
   on a small laptop and the ceiling stops it hogging an ultrawide. */
/* ⚠ --tcgc-top IS THE HOST PAGE'S HEADER, measured in attach() from the panel's PARENT (the flex
   row), never hard-coded and never taken from the panel itself — the panel carries a top margin, so
   its own offset would double-count the gutter. Without it the card slides under the site nav once
   stuck, and a viewport-height cap overruns the bottom by exactly the header's height.
   It defaults to 0, which is correct for a page with no header. */
#tcg-chat-panel {
  --tcgc-gutter: 24px;
  position: sticky; top: calc(var(--tcgc-top, 0px) + var(--tcgc-gutter));
  align-self: flex-start;
  flex: 0 0 clamp(180px, 18%, 320px); width: clamp(180px, 18%, 320px);
  margin: var(--tcgc-gutter) 0 var(--tcgc-gutter) var(--tcgc-gutter);
  box-sizing: border-box;
  /* ⚠ HEIGHT IS AUTO — THE PANEL HUGS ITS CONTENT (owner, 2026-09-22: a full-height column "makes it
     clear we are wasting precious UI real-estate"). It grows with the conversation and stops at the
     viewport, after which the stream scrolls. min-height keeps an empty panel a tidy card rather than
     a sliver. */
  height: auto;
  min-height: 160px;
  max-height: calc(100vh - var(--tcgc-top, 0px) - (var(--tcgc-gutter) * 2));
  display: flex; flex-direction: column; gap: 8px; padding: 14px 14px 12px;
  color: #eef2f6;
  /* ⚠ A NEUTRAL, LEGIBLE BASE, NOT A SKIN. Every sim with a waiting room gets this file and they do
     not share a palette, so SWUSim paints the Petranaki HUD glass over the top from
     SharedUI/Sites/SWUSim/css/swusim-overrides.css — exactly as .wr-panel and the site pages do.
     What matters here is only that chat text stays READABLE over whatever background art the host
     page has; an early cut used rgba(8,14,22,.55) with no blur and dissolved into SWUSim's backdrop. */
  background: linear-gradient(165deg, rgba(30,36,44,.88) 0%, rgba(16,20,26,.94) 100%);
  -webkit-backdrop-filter: blur(10px) saturate(112%);
  backdrop-filter: blur(10px) saturate(112%);
  border: 1px solid rgba(196,208,220,.20);
  border-radius: 4px;
}
.tcgc-label { font-size: 12px; font-weight: 600; letter-spacing: .08em; color: #9fadbd; flex: 0 0 auto; }
/* ⚠ flex: 0 1 auto, NOT 1 1 auto. The panel now sizes to its content, so a stream that GREEDILY
   claimed free space would stretch the card back to full height and undo the whole point. It takes
   the height it needs, shrinks when the card hits its max, and only then scrolls.
   min-height:0 is required or a flex child refuses to shrink below its content and pushes the
   composer off the bottom. */
#tcgc-stream { flex: 0 1 auto; min-height: 0; overflow-y: auto; overflow-x: hidden;
               font-size: 13px; line-height: 1.45; overflow-wrap: anywhere; }
/* A quiet panel should read as finished, not broken. */
#tcgc-stream:empty::after {
  content: 'No messages yet.';
  display: block; padding: 2px 0;
  font-size: 12px; font-style: italic; color: rgba(226,232,240,.45);
}
#tcgc-composer { flex: 0 0 auto; display: flex; gap: 6px; align-items: stretch; }
/* A sunken well, matching the deck field in the room beside it. An unstyled browser-default input
   next to the site's own chamfered controls is what made the first cut look bolted on. */
#tcgc-input {
  flex: 1 1 auto; min-width: 0; height: 34px; box-sizing: border-box;
  padding: 0 10px; font: inherit; font-size: 13px; color: #eef2f6;
  background: rgba(10,13,18,.55);
  border: 1px solid rgba(196,208,220,.18);
  border-radius: 3px;
  box-shadow: inset 0 1px 3px rgba(0,0,0,.45);
}
#tcgc-input::placeholder { color: rgba(226,232,240,.45); }
#tcgc-input:focus { outline: none; border-color: rgba(233,184,102,.65); }
#tcgc-send {
  flex: 0 0 auto; height: 34px; padding: 0 12px; cursor: pointer;
  font: inherit; font-size: 11px; font-weight: 700; letter-spacing: .10em; text-transform: uppercase;
  color: #eef2f6; background: rgba(62,70,81,.55);
  border: 1px solid rgba(196,208,220,.22); border-radius: 3px;
}
#tcgc-send:hover { background: rgba(78,88,101,.62); }
#tcgc-note { flex: 0 0 auto; font-size: 12px; color: #9fadbd; }
#tcgc-note:empty { display: none; }

/* Seat rails — the rail is what separates conversation from game events at a glance; the name tint
   is secondary. Same four colours as the in-game log. */
/* Owner, 2026-09-26: a border on every message, log or chat, so the eye can tell one from the
   next -- then "a little thicker and a little bolder": 2px at 0.14 alpha, up from a 1px hairline
   at 0.07 which was too easy to miss against the panel.
   A SEPARATOR rather than a box per row: in a hundred-line game log, a hundred boxes is noise,
   and what makes a wall of text parseable is knowing where each entry ends.
   The last row has no rule under it -- a trailing separator reads as a missing message.
   ⚠ Kept in step with the in-game log (SWUSim/Custom/GameLayout.php .swu-log-entry): the three
   chat surfaces -- lobby, sideboard, board -- should not drift apart. */
.tcgc-row {
  padding: 4px 0 4px 8px;
  border-left: 3px solid transparent;
  border-bottom: 2px solid rgba(255, 255, 255, 0.14);
}
.tcgc-row:last-child { border-bottom: 0; }
.tcgc-row.tcgc-p1 { border-left-color: #6fb8ff; } .tcgc-row.tcgc-p1 .tcgc-who { color: #6fb8ff; }
.tcgc-row.tcgc-p2 { border-left-color: #ff9b6f; } .tcgc-row.tcgc-p2 .tcgc-who { color: #ff9b6f; }
.tcgc-row.tcgc-p3 { border-left-color: #7fd88f; } .tcgc-row.tcgc-p3 .tcgc-who { color: #7fd88f; }
.tcgc-row.tcgc-p4 { border-left-color: #d79bff; } .tcgc-row.tcgc-p4 .tcgc-who { color: #d79bff; }
.tcgc-row.tcgc-log { color: #aab6c4; border-left-color: #ffffff14; }
.tcgc-who { font-weight: 600; }
.tcgc-loghead { margin: 6px 0 2px; font-size: 11px; letter-spacing: .08em; color: #9fadbd; }

#tcg-chat-toggle { display: none; position: fixed; left: 8px; top: 50%; z-index: 40;
                   width: 40px; height: 40px; border-radius: 8px; font-size: 18px; cursor: pointer;
                   border: 1px solid #ffffff2f; background: rgba(8,14,22,.9); color: #cdeeff; }

/* Below 900px the column stops taking width and becomes a drawer over the content (owner, 2026-09-22).
   ⚠ flex-basis must be reset to auto here: a fixed-position element still honours flex-basis from the
   rule above in some engines, which reserves a gap in the flex row that nothing fills. */
/* The drawer OWNS the viewport, so it is the one place the panel is full height — it is not competing
   with page content for space there. margin/min-height are reset or it would sit inset from the edge. */
@media (max-width: 900px) {
  #tcg-chat-toggle { display: block; }
  #tcg-chat-panel { position: fixed; left: 0; top: 0; z-index: 41;
                    width: min(86vw, 340px); flex-basis: auto;
                    margin: 0; height: 100vh; max-height: 100vh; min-height: 0; border-radius: 0;
                    transform: translateX(-102%); transition: transform .18s ease; }
  #tcg-chat-panel[data-open="1"] { transform: translateX(0); }
}
/* Someone who asked not to see motion should not be swept at by a drawer. */
@media (prefers-reduced-motion: reduce) { #tcg-chat-panel { transition: none; } }

/* ⚠ NOTHING TO TALK IN, NOTHING TO SHOW. The panel ships inert and the host page calls attach() once
   it knows which conversation this is — so on a page with no lobby at all (WaitingRoom.php with no
   ?lobby=, which renders "No lobby specified.") an always-visible panel is an empty column taking
   18% of the screen for nothing. These rules come AFTER the media query on purpose: id+attribute
   outranks the bare id that shows the toggle below 900px, so a not-yet-attached panel stays hidden
   at every width. */
#tcg-chat-panel[data-ready="0"], #tcg-chat-toggle[data-ready="0"] { display: none; }
</style>
CSS;
}

function _ChatPanelScript(): string {
    $body = <<<'JS'
(function () {
  'use strict';
  var cfg = null, timer = null, lastId = 0;
  var panel  = document.getElementById('tcg-chat-panel');
  var stream = document.getElementById('tcgc-stream');
  var input  = document.getElementById('tcgc-input');
  var sendBtn = document.getElementById('tcgc-send');
  var note   = document.getElementById('tcgc-note');
  var toggle = document.getElementById('tcg-chat-toggle');
  var OPEN_KEY = 'tcg:chatPanelOpen:' + location.pathname;

  function esc(s) {
    return String(s === null || s === undefined ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }
  function base() {
    var i = location.pathname.indexOf('/TCGEngine/');
    return i >= 0 ? location.pathname.slice(0, i + '/TCGEngine/'.length) : '/TCGEngine/';
  }
  function scopeParams() {
    var s = (cfg && cfg.scope) || {};
    if (s.lobbyID) return 'lobbyID=' + encodeURIComponent(s.lobbyID);
    if (s.matchId) return 'matchId=' + encodeURIComponent(s.matchId);
    return 'gameName=' + encodeURIComponent(s.gameName || '');
  }
  // The in-game rule, deliberately: re-pin ONLY when the reader is already at the bottom, so a new
  // message never yanks someone out of scrollback.
  function atBottom() { return (stream.scrollHeight - stream.scrollTop - stream.clientHeight) < 60; }

  function addRow(cls, who, text, ts) {
    var pinned = atBottom();
    var row = document.createElement('div');
    row.className = 'tcgc-row ' + cls;
    row.innerHTML = (who ? '<span class="tcgc-who">' + esc(who) + '</span> ' : '') + esc(text);
    insertByTs(row, ts, false);   // a chat row is never history
    if (pinned) stream.scrollTop = stream.scrollHeight;
  }

  // A log line can NAME CARDS. The server sends the line with [[SET_NNN]] tokens and a separate
  // name map (SWUSim/GetGameLog.php), because only the server has the card dictionary.
  //
  // ⚠ BUILT AS DOM NODES, NEVER innerHTML. Every other row in this panel is user-authored chat
  // and goes through esc(); a card name arrives from the dictionary but still lands as a TEXT node,
  // so there is no path here that can turn a name into markup. The token itself is matched with the
  // same shape GetGameLog.php uses -- set codes contain DIGITS (TS26_01, IC27_001), so the set part
  // is [A-Z0-9], not [A-Z].
  // ⚠ ROWS ARRIVE IN WHATEVER ORDER THEIR REQUESTS RESOLVE. The chat poll is a plain read; a game
  // log has to be parsed out of a gamestate, so the chat almost always wins and the log used to be
  // appended UNDERNEATH it -- which put the message you just sent at the TOP of the panel, above a
  // wall of log lines. Owner, 2026-09-26: "after many game logs, [it] is hard to find."
  //
  // So the LOG SECTION IS ANCHORED: log blocks sit at the top of the stream, in the order they were
  // added, and chat always continues below them. The two sources share no clock, so nothing is
  // re-sorted -- but the layout is deterministic now instead of a race.
  // ⚠ ROWS ARRIVE IN WHATEVER ORDER THEIR REQUESTS RESOLVE. The chat poll is a plain read; a game
  // log is parsed out of a gamestate. So the stream is ordered by the rows' OWN timestamps, not by
  // arrival: chat carries `ts` (SubmitChat.php) and a log line carries '@<microtime>' in field 2
  // (AddGameLogEntry). Both are wall-clock microtime, which is the only reason they can interleave.
  //
  // A row with NO timestamp appends, which is what every legacy log line and every other sim's
  // caller does -- so nothing that worked before changes shape.
  //
  // Scans from the END because rows normally arrive in order, so the common case is one comparison.
  function insertByTs(node, ts, isHistory) {
    var hasTs = (ts !== null && ts !== undefined && !isNaN(ts));
    if (hasTs) node.setAttribute('data-ts', ts);
    if (!hasTs) {
      // ⚠ WHAT AN UNSTAMPED ROW MEANS DEPENDS ON WHICH KIND IT IS.
      // A LOG line without a stamp is HISTORY -- a game played before 2026-09-26 -- and belongs
      // above everything we can place. A CHAT row without one is a message that just ARRIVED with
      // an unknown time, and belongs at the bottom. Using the history rule for both sent every
      // such message to the top of the panel, which was worse than the original bug.
      if (!isHistory) { stream.appendChild(node); return; }
      // Appending it instead was the bug: a game log from before 2026-09-26 carries no timestamps,
      // so a message sent today (which does) was inserted first and the whole log landed UNDER it
      // -- "i send a chat, then refreshed and it moved to the top". Every game already in progress
      // has an unstamped log, so this is the normal case right now, not an edge case.
      // Unstamped rows keep their own arrival order: each goes before the first STAMPED row, which
      // is after any unstamped row already there.
      var firstStamped = null;
      for (var j = 0; j < stream.children.length; j++) {
        if (!isNaN(parseFloat(stream.children[j].getAttribute('data-ts')))) { firstStamped = stream.children[j]; break; }
      }
      stream.insertBefore(node, firstStamped);   // null => append, correct when nothing is stamped
      return;
    }
    var rows = stream.children;
    for (var i = rows.length - 1; i >= 0; i--) {
      var t = parseFloat(rows[i].getAttribute('data-ts'));
      if (!isNaN(t) && t <= ts) { stream.insertBefore(node, rows[i].nextSibling); return; }
    }
    // older than everything stamped: go above them, but below any unstamped preamble
    var first = stream.firstChild;
    while (first && first.nodeType === 1 && isNaN(parseFloat(first.getAttribute('data-ts')))) first = first.nextSibling;
    stream.insertBefore(node, first);
  }

  function addLogRow(text, cards, ts) {
    var pinned = atBottom();
    var row = document.createElement('div');
    row.className = 'tcgc-row tcgc-log';
    // ⚠ TWO TOKEN FORMS, and both are in real logs:
    //   [[SEC_111|Jar Jar Binks]]  GameLogCardRef() -- the NAME IS EMBEDDED (the engine has the
    //                              dictionary at write time). This is the common one.
    //   [[SEC_111]]                the bare form, named from the map GetGameLog.php builds.
    // Matching only the bare form left "[[SEC_111|Jar Jar Binks]]" on screen verbatim.
    var re = /\[\[([A-Za-z0-9]{2,6}_[A-Za-z0-9]{2,5})(?:\|([^\]]*))?\]\]/g;
    var last = 0, m;
    while ((m = re.exec(text)) !== null) {
      if (m.index > last) row.appendChild(document.createTextNode(text.slice(last, m.index)));
      var id = m[1];
      var el = document.createElement('span');
      el.className = 'tcgc-card';
      el.setAttribute('data-card-id', id);
      // the embedded name wins; otherwise the server's map; otherwise the id itself
      el.textContent = m[2] || (cards && cards[id]) || id;   // text node: a name cannot be markup
      row.appendChild(el);
      last = re.lastIndex;
    }
    if (last < text.length) row.appendChild(document.createTextNode(text.slice(last)));
    insertByTs(row, ts, true);    // an unstamped LOG line is history
    if (pinned) stream.scrollTop = stream.scrollHeight;
  }

  function poll() {
    fetch(base() + 'GetChat.php?' + scopeParams() +
          '&lastChatID=' + lastId +
          '&playerID=' + encodeURIComponent(cfg.playerID) +
          '&authKey='  + encodeURIComponent(cfg.authKey) +
          '&folderPath=' + encodeURIComponent(cfg.folderPath))
      .then(function (r) { return r.json(); })
      .then(function (rows) {
        if (!Array.isArray(rows)) return;
        rows.forEach(function (m) {
          if (m.id > lastId) lastId = m.id;
          var seat = parseInt(m.playerID, 10);
          var cls = (seat >= 1 && seat <= 4) ? 'tcgc-p' + seat : 'tcgc-log';
          // `ts` is microtime; `time` is the legacy whole-second field, used when a message
          // predates the ts column so it still lands somewhere sensible.
          var mts = (m.ts !== undefined && m.ts !== null) ? parseFloat(m.ts)
                  : (m.time !== undefined ? parseFloat(m.time) : NaN);
          addRow(cls, (m.playerLabel || ('P' + m.playerID)) + ':', m.text, mts);
        });
      })
      .catch(function () { /* a dropped poll is not an error the player needs to see */ })
      .finally(function () { timer = setTimeout(poll, 2000); });
  }

  function send() {
    var text = (input.value || '').trim();
    if (!text) return;
    input.value = '';
    fetch(base() + 'SubmitChat.php?' + scopeParams() +
          '&playerID=' + encodeURIComponent(cfg.playerID) +
          '&authKey='  + encodeURIComponent(cfg.authKey) +
          '&folderPath=' + encodeURIComponent(cfg.folderPath) +
          '&chatText=' + encodeURIComponent(text))
      .then(function (r) { return r.text(); })
      .then(function (t) { if (t.trim() !== 'OK') note.textContent = t.trim(); });
  }

  // How far down the viewport the panel starts — i.e. the host page's header. Measured, never
  // assumed: the Waiting Room has a nav bar and the Sideboard has none, and hard-coding either would
  // clip the composer on the other. Read at the DOCUMENT's scroll-top position so the number is the
  // header's height rather than wherever the reader happens to be scrolled to.
  // ⚠ MEASURED FROM THE PARENT, not from the panel. The panel carries a top margin (the gutter), so
  // its own offset is header + gutter and using it would count the gutter twice — the card would
  // creep down the page and its max-height would be short by 24px.
  function measureTop() {
    if (window.matchMedia('(max-width: 900px)').matches) {
      panel.style.removeProperty('--tcgc-top');   // the drawer is fixed at 0 and owns the viewport
      return;
    }
    var host = panel.parentElement || panel;
    var top = host.getBoundingClientRect().top + (window.scrollY || window.pageYOffset || 0);
    panel.style.setProperty('--tcgc-top', Math.max(0, Math.round(top)) + 'px');
  }

  function setOpen(open) {
    panel.setAttribute('data-open', open ? '1' : '0');
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    try { localStorage.setItem(OPEN_KEY, open ? '1' : '0'); } catch (e) {}
  }

  window.TCGChatPanel = {
    attach: function (options) {
      cfg = options || {};
      panel.setAttribute('data-ready', '1');     // reveals it — see the [data-ready="0"] rules
      toggle.setAttribute('data-ready', '1');
      // canSend comes from the server's own policy answer (Core/ChatPolicy.php), so the composer and
      // the endpoint can never disagree about whether this viewer may talk.
      if (!cfg.canSend) {
        document.getElementById('tcgc-composer').style.display = 'none';
        note.textContent = cfg.cannotSendReason || 'Log in to chat.';
      }
      sendBtn.onclick = send;
      input.onkeydown = function (e) { if (e.key === 'Enter') { e.preventDefault(); send(); } };
      toggle.onclick = function () { setOpen(panel.getAttribute('data-open') !== '1'); };
      try { setOpen(localStorage.getItem(OPEN_KEY) === '1'); } catch (e) { setOpen(false); }
      measureTop();
      window.addEventListener('resize', measureTop);
      if (timer) clearTimeout(timer);
      poll();
    },
    detach: function () { if (timer) { clearTimeout(timer); timer = null; } },
    // opts.cards maps a [[SET_NNN]] token to a display name. Absent, a line renders exactly as it
    // always did, so every existing caller is unaffected.
    appendLog: function (heading, lines, opts) {
      opts = opts || {};
      // A line is a plain string (every existing caller) or {text, ts} when it is stamped.
      var rows = (lines || []).map(function (l) {
        return (l && typeof l === 'object') ? { text: String(l.text || ''), ts: parseFloat(l.ts) }
                                            : { text: String(l), ts: NaN };
      });
      if (heading) {
        var h = document.createElement('div');
        h.className = 'tcgc-loghead';
        h.textContent = heading;
        // the heading sits with the first line it introduces
        insertByTs(h, rows.length ? rows[0].ts : NaN, true);
      }
      rows.forEach(function (r) { addLogRow(r.text, opts.cards || null, r.ts); });
      stream.scrollTop = stream.scrollHeight;
    }
  };
})();
JS;
    return '<script>' . $body . '</script>';
}
