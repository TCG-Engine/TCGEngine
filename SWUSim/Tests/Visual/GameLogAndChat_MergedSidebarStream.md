# VISUAL CHECK — Game Log and Chat merged into ONE sidebar stream, with real usernames
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
#   GN=$(curl -s -X POST .../SWUSim/TestSchemaSetup.php --data-urlencode "schema@<this file>" | grep -o '"gameName":[0-9]*' | grep -o '[0-9]*')
#   open "http://localhost:3400/TCGEngine/NextTurn.php?folderPath=SWUSim&gameName=$GN&playerID=1&authKey=testschema"
#   Speak into it from either seat WITHOUT a second browser:
#     fetch('./SubmitChat.php?gameName='+GN+'&playerID=2&authKey=testschema&folderPath=SWUSim&chatText=hi')
#
# WHAT THIS PINS (reported 2026-08-21)
#   "let's combine the gamelog with the chatlog. it seems players are not understanding that it can be
#    switched between." — the sidebar had a Log/Chat TAB BAR, defaulting to Log with chat hidden behind
#   an unnoticed tab. Messages arrived in a panel nobody had open. There is now no tab bar: one
#   scrolling #swuLogPanel carries game events AND chat, with the composer pinned under it.
#   Second half of the report: "if a player is logged in, print their username when they send a chat."
#
# THE SEAM (two halves, either can break independently)
#   1. Core/jsInclude.js _AppendChatMessage builds the row, then offers it to an optional host sink,
#      window.TCGChatMessageSink(el, msg). Anything but a `false` return means "placed" — and a placed
#      message gets NO toast, because it is already on screen. SWUSim's sink appends into #swuLogPanel.
#      Other sims define no sink and keep the old #chatLog behaviour untouched.
#   2. window.SWU_SEAT_USERNAMES — seat -> username. ⚠ This global had TWO consumers and NO PRODUCER:
#      _ChatPlayerLabel (chat labels) and SWUBuildBlockPlayerWidget. Chat therefore always read "P1"/"P2"
#      and the Block Player widget silently returned null for EVERYONE (it bails when the viewer has no
#      name). GameLayoutShared.php now emits it from MatchSeatDisplayNames().
#
# WHAT TO LOOK AT (desktop, 1700x1050)
#   • The sidebar has NO Log/Chat tabs. A single "GAME LOG & CHAT" label sits above one scrolling panel.
#   • Send a message from each seat. Each lands INLINE in that one panel, in arrival order, among the
#     game events — not in a separate box.
#   • Each chat row carries a seat-tinted left RAIL and a matching coloured name:
#       P1 #6fb8ff · P2 #ff9b6f · P3 #7fd88f · P4 #d79bff
#     ⚠ The rail is what separates conversation from game events at a glance; the tint is secondary.
#   • The composer (input + Send) is a fixed row at the BOTTOM of the sidebar, always visible — it no
#     longer stretches to fill the panel (#chatWidget is flex:0 0 auto and #chatExpanded is hidden,
#     because the history now renders upstream in the log).
#   • ★ AUTOSCROLL: with the panel scrolled to the bottom, a new message keeps it pinned. Scroll UP
#     first, then send — the panel must NOT yank you back down (the sink only re-pins when already
#     within 60px of the bottom, matching swuRenderGameLog).
#
# USERNAMES — ⚠ THIS FIXTURE CANNOT SHOW THEM, AND THAT IS THE TRAP
#   A TestSchemaSetup game has no match record, so SWU_SEAT_USERNAMES is {} and every row correctly
#   reads "P1:"/"P2:". A green run here says NOTHING about the username path. To check that half you
#   need a game spawned from a real lobby by LOGGED-IN accounts (creds in .claude/CLAUDE.md), or an
#   existing one:
#     docker exec ... grep -l '"userId":[1-9]' SWUSim/Matches/*/Match.json
#   then open that match's gameName and assert window.SWU_SEAT_USERNAMES is populated.
#
# MATCHLESS MODES (goldfish / hotseat) — the case the match lookup alone MISSES
#   These never create a match record, so the seat loop names nobody and a logged-in player asking
#   "why isn't my own name showing?" is a legitimate report. A second pass fills the VIEWER'S OWN seat
#   from their session (LoggedInUser -> MatchHistoryUsername), mirroring the fallback
#   SWUBuildCosmeticsPayload already uses for these modes.
#   ⚠ Only the viewer's own seat can be filled: goldfish seat 2 is a dummy and hotseat seat 2 is the
#   same physical person on the same browser, so neither is a known account and both correctly stay
#   "P2". That also keeps Block Player hidden here — it needs BOTH names, and there is nobody to block.
#   ⚠ ONLY REAL USERNAMES MAY APPEAR IN IT. MatchSeatDisplayNames substitutes "Player N" for an
#   anonymous seat, and BOTH consumers read a missing entry as "not logged in" — publishing the
#   fallback would label a guest as an account and offer a Block button that cannot work. The emitter
#   therefore gates on userId > 0, not on the display string. A guest seat must still read "P1".
#
# NARROW DESKTOP / MOBILE
#   • <800px desktop: #swuSidebar (and the log panel) is display:none and the floating "💬 Chat"
#     launcher returns. The sink DECLINES there (returns false) and Core falls back to #chatLog + its
#     toast, which is the only visible surface. ⚠ Detection is a VISIBLE #chatToggleBtn, not a width
#     test — the mobile layout kills that button outright, so mobile always takes the merged path even
#     while its drawer is closed. If that button is ever un-hidden on mobile, mobile chat silently
#     leaves the log.
#   • Mobile layout: same merge, same rail colours. ⚠ Its #swuLogLabel is styled EXPLICITLY in
#     GameLayoutMobile.php — that file does not inherit GameLayout's CSS, so the shared
#     .swu-sidebar-section-label class alone left the heading at browser-default size.
#
# ORDERING — a known, accepted approximation
#   Game log entries are "TYPE|VISIBILITY|text" with NO timestamp, so true chronological interleaving is
#   not available. Rows are appended in ARRIVAL order. Ongoing play is therefore correctly interleaved
#   (log and chat ride the same poll); only on FIRST LOAD does the chat backlog land after the log
#   backlog. Do not read that as a bug.
#
# AUTOMATED PROBE (what was actually run)
#   Real games driven through the production SubmitChat.php endpoint, asserting the DOM:
#     no #swuTabBar · window.swuShowTab gone · TCGChatMessageSink defined · chat rows land in
#     #swuLogPanel with class swu-log-CHAT + chatMsg-pN · rail colour matches the seat ·
#     ZERO strays left in #chatLog · panel pinned to bottom · no horizontal overflow ·
#     composer + Send visible · #chatExpanded hidden · #chatToggleBtn hidden
#   Chromium: ALL PASS      Firefox: ALL PASS
#   Username half, on match M791 / game 3303 (both seats logged in as "ninin"):
#     SWU_SEAT_USERNAMES = {"1":"ninin","2":"ninin"} · both rows labelled "ninin:" with distinct
#     chatMsg-p1 / chatMsg-p2 classes · SWUBuildBlockPlayerWidget() now returns a widget (it returned
#     null before this change, for every player, in every game).
#   Guest control, on game 3497 (no match record): SWU_SEAT_USERNAMES = {} and the row reads "P3:".
#   Matchless matrix, Chromium + Firefox, on a real goldfish game created from the main menu:
#     goldfish + logged in (claudebot1) -> {"1":"claudebot1"}, row reads "claudebot1:"
#     goldfish + GUEST (fresh session)  -> {},                 row reads "P1:"
#     match game + no login             -> {"1":"ninin",...},  row reads "ninin:"   (identity is the
#       GAME's, not the viewer's — a spectator or a not-logged-in seat still sees the real names)
#   ⚠ WebKit could NOT be verified on this machine: playwright's webkit LAUNCHES but the first
#     newPage()/about:blank never completes (>90s). Safari is UNVERIFIED for this change.
#
# NOT COVERED HERE
#   Chat toasts (only reachable in the <800px floating fallback) and the blocked-player "Chat disabled"
#   state, both untouched by this change.

## GIVEN
#// Two seats is the shape that matters here — the sidebar is identical in Twin Suns, and the seat-3/4
#// rail colours are asserted by the probe rather than by this board.
CommonSetup: bbw/rrk/{myResources:5; theirResources:5}
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP1GroundArena: [SOR_032:1:0 SOR_033:1:2]
WithP2GroundArena: [SOR_034:1:0 SOR_035:1:2]

## WHEN

## EXPECT
TURNPLAYER:1
