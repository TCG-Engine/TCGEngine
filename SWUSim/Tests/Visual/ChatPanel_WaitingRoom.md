# VISUAL CHECK — the chat panel, pinned left in the Waiting Room
#
# Visual-only (Tests/Visual/ is not scanned by the regression endpoint).
#
# SETUP
#   Log in as claudebot1 (creds in .claude/CLAUDE.md), create a private twinsuns room, join it from a
#   second browser context as claudebot2, and open the room in both:
#     http://localhost:3400/TCGEngine/SharedUI/Sites/SWUSim/WaitingRoom.php?lobby=<id>
#   The page reads its authKey from localStorage under tcg:lobbyAuth:<lobbyID>, so a context that did
#   not do the join itself must seed that key (the harness does).
#
# WHAT THIS PINS
#   Owner request 2026-09-22: "game log and chat ... pinned to the left ... about 15-20% of the screen."
#   D1: the Waiting Room gets CHAT ONLY — there is no game yet, so there is no log to show.
#   D3: below 900px it collapses to a toggle rather than eating a phone's width.
#   D4: FaBSim shares this renderer and gets the panel too.
#
# ★ THE COMPONENT IS BASE + SKIN, AND THAT SPLIT IS THE POINT (owner, 2026-09-22)
#   SharedUI/Render/ChatPanel.php holds the markup, the behaviour and a NEUTRAL legible base — it is
#   shared with FaBSim, which has a different palette. The New Petranaki HUD skin lives in
#   SharedUI/Sites/SWUSim/css/swusim-overrides.css ("Chat panel" block), scoped to the component's own
#   id and joined to the same ::before glass / ::after shadow selector lists that .wr-panel and the
#   site pages already use. So EVERY SWUSim screen that mounts the component looks identical for free,
#   and FaB is untouched. Do not re-declare the blur or the corner glows in the component — they would
#   drift from every other surface.
#   Verify the seam both ways: SWUSim's panel has a ::before with a polygon clip-path (the chamfer);
#   FaB's has content:none and keeps its gradient base.
#
# ★ IT HUGS ITS CONTENT — NOT A FULL-HEIGHT COLUMN (owner, 2026-09-22: a full-height panel "makes it
#   clear we are wasting precious UI real-estate"). Measured: 160px empty, 894px after 60 rows,
#   capped at the viewport, after which the stream scrolls. The background art shows through below it.
#   ⚠ #tcgc-stream is flex: 0 1 auto. At 1 1 auto it greedily claims free space and stretches the card
#   back to full height, undoing the whole thing while every other assertion stays green.
#
# WHAT TO LOOK AT (desktop, 1700x1050)
#   • The panel is a Petranaki glass CARD in the left column: chamfer cut at top-left and
#     bottom-right with sandy-gold corner glows, frosted stone-grey panel, hairline rim, drop shadow.
#     Its top edge lines up with the ROOM panel's.
#   • It measures 18% of the viewport, clamped to 180-320px. At 1700px that is 306px.
#   • Empty, it reads "No messages yet." (a #tcgc-stream:empty::after) rather than a blank slab.
#   • The roster keeps its 2x2 grid. ⚠ That grid is an EXPLICIT 2-track grid with a 760px media query,
#     NOT auto-fit — narrowing the content area must not silently make it 1x4 or 3-up.
#   • A message from each seat lands in the panel with a seat-tinted left RAIL and a matching name
#     colour: P1 #6fb8ff · P2 #ff9b6f · P3 #7fd88f · P4 #d79bff. Same palette as the in-game log, so
#     players meet one visual language.
#   • The composer is a fixed row at the BOTTOM, always visible, never stretched to fill.
#   • ★ AUTOSCROLL: scrolled to the bottom, a new message keeps the panel pinned. Scroll UP first,
#     then send from the other seat — the panel must NOT yank you back down. The rule is the in-game
#     one: re-pin only when already within 60px of the bottom.
#
# ★★ TWO THINGS THE ASSERTIONS DID NOT CATCH, BOTH FOUND BY LOOKING AT THE SCREENSHOT
#   1. THE PANEL DISSOLVED INTO THE BACKGROUND. The first cut used rgba(8,14,22,.55) with no blur;
#      over SWUSim's cave art the column had no edge and the chat text sat directly on rock. Every
#      geometry assertion was green. The base style is now an opaque-ish dark glass with a blur and a
#      hairline right edge, and the composer is a sunken well + chamfer-ish button rather than
#      browser defaults, which is what made it look bolted on.
#   2. ★ THE COMPOSER WAS CUT OFF THE BOTTOM OF THE SCREEN. The host page has a nav bar above the
#      flex row, so `top:0; height:100vh` overran the viewport by exactly the header's height. The
#      panel now measures its PARENT's offset into --tcgc-top (its own would double-count the gutter
#      it carries) and caps with max-height: calc(100vh - var(--tcgc-top) - gutter*2).
#      Measured by mutation: reverting that puts the composer's bottom at 1146px in a 1050px
#      viewport — 96px off screen, with every other assertion still green. Two assertions now guard
#      it ("the composer is fully on screen", "the panel does not overrun the viewport").
#   ⚠ THE LESSON: geometry assertions confirm a box is where you computed it should be. They cannot
#     tell you the box is the wrong size, invisible, or under something else. LOOK AT THE IMAGE.
#
# NO LOBBY, NO PANEL
#   The panel ships inert (data-ready="0", display:none) and the page calls attach() once its poll
#   knows the lobbyID. On WaitingRoom.php with no ?lobby= — which renders "No lobby specified." — an
#   always-visible panel was an empty 18% column for nothing. It now stays hidden at every width.
#
# GUESTS — the case a logged-in run CANNOT show
#   Owner ruling 2026-09-21: a guest may PLAY but not CHAT. Open the room in a fresh context with no
#   session: the stream renders and is readable, and there is NO composer — replaced by the line
#   "Log in to chat." The flag comes from PollLobbyUpdates' canChat, which asks the SAME seam
#   (Core/ChatPolicy.php) that SubmitChat.php enforces, so the UI cannot offer a box whose message
#   would be refused.
#
# NARROW (390px)
#   • The panel is off-screen; a 💬 button is pinned to the left edge.
#   • Tapping it slides the panel over the content as a drawer.
#   • The roster is full width. No horizontal page scroll.
#   • The expanded/collapsed choice survives a reload (localStorage, per screen path).
#
# AUTOMATED PROBE (what was actually run)
#   DevTools/ui-harness/chatpanel-xbrowser.mjs — a REAL private twinsuns room with two logged-in
#   accounts, messages sent through the production SubmitChat.php:
#     panel width == clamp(180,18vw,320) · pinned left · roster still 2 grid tracks ·
#     no horizontal scroll at 1700 or 390 · message reaches the OTHER seat · rail colour
#     rgb(111,184,255) + class tcgc-p1 · autoscroll leaves a scrolled-up reader at scrollTop 0 ·
#     composer fully on screen · panel does not overrun the viewport · stuck panel clears the site
#     header · guest has no composer and is told why · 390px: off-screen + toggle shown + drawer
#     slides in on tap
#     plus, after the Petranaki re-skin: empty panel is a compact card (160px of 1050px) and not a
#     sliver · the ::before glass layer is painted with its chamfer clip · the panel itself is
#     transparent so the chamfer is not squared off · the card GROWS with the conversation
#     (160 -> 894px) and CAPS at the viewport
#   Chromium: 40/40 PASS   Firefox: 40/40 PASS   WebKit: 40/40 PASS   (2026-09-22, both screens)
#   ⚠ WebKit LAUNCHED AND PASSED on this machine. Earlier notes saying it cannot be launched from the
#     ui-harness are stale — see the memory note verifying-swudeck-ui-cross-browser.
#
# FaBSim — NOT OPTIONAL (owner chose "both sims get it", 2026-09-22)
#   The Waiting Room page is SHARED. Verified 2026-09-22 on http://localhost:3500/: the panel renders
#   at 306px, clears FaB's header, no horizontal scroll, and its neutral dark glass suits FaB's
#   palette. With no lobby, panel and toggle are both display:none. ⚠ Re-verified after the Petranaki
#   re-skin: FaB's ::before is content:none and its gradient base survives — the skin is SWUSim-only,
#   which is what makes the component re-skinnable rather than SWUSim-shaped. FaB has NO login gate, so a FaB
#   guest correctly DOES get a composer — that is FaB's rule, not a bug, and it is why the composer is
#   gated on the per-sim policy rather than on a hardcoded SWUSim check.
#
# NOT COVERED HERE
#   The Sideboard mount (ChatPanel_Sideboard.md) · whispers, which stay game-only · the in-game
#   sidebar, untouched by this change.

## GIVEN
#// No board state: this page is not the game. The section exists so the file parses like its
#// neighbours; the check above is entirely manual + harness-driven.
CommonSetup: bbw/rrk/{myResources:5; theirResources:5}
WithGamePhase: ActionPhase
WithActivePlayer: 1

## WHEN

## EXPECT
TURNPLAYER:1
