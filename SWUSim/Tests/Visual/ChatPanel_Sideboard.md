# VISUAL CHECK — chat + the previous game's log, pinned left in the Sideboard
#
# Visual-only (Tests/Visual/ is not scanned by the regression endpoint).
#
# SETUP
#   Fastest: the local-dev fixture builds a finished game 1 and parks a Bo3 in sideboarding —
#     curl http://localhost:3400/TCGEngine/DevTools/tdd-regression/fixtures/swusim_make_sideboard.php
#   then open, as each seat (authKeys sbk1 / sbk2):
#     http://localhost:3400/TCGEngine/SWUSim/Sideboard.php?matchId=<M…>&playerID=1&authKey=sbk1
#   The real thing: play a Bo3 to the end of game 1 with two logged-in accounts.
#
# WHAT THIS PINS
#   D1 (owner, 2026-09-22): the Sideboard shows chat AND the log of the game just played — that log
#   is the reference you sideboard against.
#
# ★ SAME COMPONENT, SAME SKIN, NO EXTRA WORK
#   This screen mounts SharedUI/Render/ChatPanel.php and inherits the New Petranaki HUD look from
#   SharedUI/Sites/SWUSim/css/swusim-overrides.css automatically — nothing here styles chat. That is
#   the test of the base+skin split: if the two screens ever look different, the skin has been
#   duplicated somewhere instead of inherited. See ChatPanel_WaitingRoom.md for the seam.
#
# WHAT TO LOOK AT (desktop, 1700x1050)
#   • The panel is a Petranaki glass CARD in the left column, 18% clamped to 180-320px (306px at
#     1700): chamfer top-left / bottom-right, gold corner glows, frosted panel. It HUGS its content
#     and caps at the viewport rather than drawing a full-height slab.
#   • Above the chat rows: a "GAME 1 LOG" heading and the game's log, greyed and read-only.
#   • ★ [[SOR_014]] tokens render as PLAIN CARD NAMES ("Sabine Wren - Galvanized Revolutionary"),
#     not raw ids and not hover art. This page is standalone and does not load Core's Card().
#   • The card grid keeps its columns (80 cards here) and the Submit control is not clipped.
#   • The composer sits at the bottom; messages from the other seat arrive within ~2s.
#
# ⚠ THE NAMES COME FROM THE SERVER, AND THEY HAVE TO
#   The first cut substituted ids using the page's own `titles` map — which is built from the cards in
#   YOUR deck. Every card you do not own, i.e. the opponent's entire board and most of what a log is
#   about, stayed as a raw id on screen. GetGameLog.php now returns a `names` map built with the full
#   dictionary. `titles` remains the fallback, then the id.
#
# ★★ THE HIDDEN-INFORMATION CHECK — DO THIS ONE BY HAND, ON BOTH SEATS
#   Open the SAME match as seat 1 and seat 2 side by side. A "You drew …" line one seat sees must NOT
#   appear for the other. A Bo3's game 1 is over while games 2 and 3 are still to be played, so what
#   the opponent drew is exactly what sideboarding must not leak.
#   ⚠ AND CHECK THE NAME MAP, NOT JUST THE TEXT. `names` is its own leak vector: built from the raw
#   log instead of from the filtered lines, it would hand back the very card the filter removed. It is
#   built from the filtered lines, and the harness asserts seat 2's map does not contain seat 1's
#   card id.
#   Server-side guards: DevTools/tdd-regression/test_swusim_getgamelog.php (mutating the filter out
#   turns both ★ assertions red, and the mutant's output shows the opponent's draw being served) and
#   SWUSim/DevTools/tests/gamelog_visibility_parity_test.php (the filter cannot drift from the
#   generated reader in GetNextTurn.php).
#
# READ-ONLY
#   The endpoint parses a finished gamestate and must NEVER write it back. Pinned by
#   test_swusim_getgamelog.php: five reads leave Gamestate.txt byte-identical (mtime + md5).
#
# NARROW (390px)
#   💬 toggle pinned left; drawer overlays; the card grid keeps its full width behind it; no
#   horizontal page scroll.
#
# GUESTS
#   Not reachable here — a sideboard seat is always an authenticated match participant, so chat is
#   always sendable on this screen. The flag is still asked through Core/ChatPolicy.php rather than
#   assumed, so this screen cannot drift from the send endpoint.
#
# ⚠ AN HTML ENTITY IN THE PANEL TITLE IS DOUBLE-ESCAPED
#   RenderChatPanel() htmlspecialchars() its title, so passing 'GAME LOG &amp; CHAT' printed
#   "GAME LOG &amp; CHAT" on screen. Pass a plain '&'. Caught by eye; now asserted.
#
# AUTOMATED PROBE (what was actually run)
#   DevTools/ui-harness/chatpanel-xbrowser.mjs, second half — both seats of a real sideboarding match:
#     panel width == clamp(180,18vw,320) · "GAME 1 LOG" heading · label is not double-escaped ·
#     no raw ids in the stream · 80 deck cards still render beside the panel · no horizontal scroll ·
#     composer present · seat 1 sees Capital City and NOT Vanquish · seat 2 sees Vanquish and NOT
#     Capital City · seat 2's name map omits SOR_020 · no page errors · 390px drawer behaviour
#   Chromium: 40/40 PASS   Firefox: 40/40 PASS   WebKit: 40/40 PASS   (2026-09-22, both screens,
#   after the Petranaki re-skin)
#   ⚠ WebKit LAUNCHED AND PASSED on this machine.
#
# NOT COVERED HERE
#   The Waiting Room mount (ChatPanel_WaitingRoom.md) · whispers, which stay game-only · hover art in
#   the log · the in-game sidebar, untouched by this change.

## GIVEN
#// No board state: this page is not the game. The section exists so the file parses like its
#// neighbours; the check above is entirely manual + harness-driven.
CommonSetup: bbw/rrk/{myResources:5; theirResources:5}
WithGamePhase: ActionPhase
WithActivePlayer: 1

## WHEN

## EXPECT
TURNPLAYER:1
