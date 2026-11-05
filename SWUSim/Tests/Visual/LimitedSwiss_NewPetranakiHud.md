# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It has no GIVEN/WHEN/EXPECT. It checks the Limited room panel's Swiss rounds (SharedUI/Render/WaitingRoom.php #lt-root)
# and the deckbuilder between rounds, not a gamestate.
#
# VISUAL CHECK — a Draft pod's Swiss rounds, in the "New Petranaki HUD" style
#
#   What changed: after a Draft pod builds it plays 3 Swiss rounds (docs/superpowers/specs/2026-11-04-swusim-limited-
#   sealed-draft-design.md §3.4, §3.5). The room panel shows the round's tables, the 15-minute break (Edit deck, Ready, the
#   host's Start round) and the standings; the builder works between rounds with the seat's last deck.
#   Client: SWUSim/Limited/ui/limited-room.js (tablesList / standingsTable), SWUSim/Limited/ui/limited-builder.js.
#   Data: SWUSim/Limited/LimitedRoom.php SWULimitedSwissView; rules SWUSim/Limited/LimitedSwissState.php.
#   Styles: SharedUI/Sites/SWUSim/css/swusim-menu-2.css "Swiss rounds", scoped `body:has(#wr-root) .wr-panel`.
#
#   The bugs it guards: a seat with a table left sitting in the room (it is sent to its board once per game); the bye seat
#   not told it has a bye; Start round enabled before everyone is Ready; the standings table pushing the page sideways at
#   390px (the three tiebreak columns fold into one line under the name).

## HOW TO RUN

Automated half (Chromium / Firefox / WebKit, 1440px and 390px):
    cd DevTools/ui-harness && node swusim-limited-room-xbrowser.mjs [BASE] [SHOTS_DIR]      (sections "swiss round / between / done")
    cd DevTools/ui-harness && node swusim-limited-builder-xbrowser.mjs [BASE] [SHOTS_DIR]   (section "between rounds")
Fixtures: SWUSim/DevTools/tests/limited_fixture.php?mode=draft&players=4|5&phase=round1|between|done (&bestOf=3).
Whole events, 3 and 8 claudebots: node swusim-limited-draft-sim.mjs --swiss (SWUSim/Tests/Visual/LimitedDraftSim.md).

## WHAT TO LOOK AT (in order; each one can fail)

1. **A round is on (a seat with a bye).** "DRAFT · PLAYING", "ROUND 1 OF 3", then one row per table — "A vs B", the game
   score "0–0" and a green PLAYING chip (DONE once finished) — and the bye row "C — bye". Your own row has a gold rim on
   the left. Under it: "You have a bye this round." and no game button.
2. **A round is on (a seat with a table).** Opening the room sends you straight to your board, once per game. Coming back
   to the room shows "Back to your game".
3. **The break.** "ROUND 2 STARTS IN" and a large m:ss clock counting down in place (red under a minute); one line saying
   what to do; Edit deck, Ready, and (host only) "Start round 2" — disabled until every seat shows a green READY chip.
   Pressing Ready turns your chip green and the button reads "Ready ✓", disabled.
4. **Standings.** "STANDINGS AFTER ROUND 1": #, Player, Pts, W-L-D, OMW, GW, OGW, percentages to one decimal; a dropped
   player's row is faded and struck through.
5. **390px.** No sideways scroll. The buttons stack full width. The OMW / GW / OGW columns are gone and each name has a
   small line under it: "OMW 33.3 · GW 100.0 · OGW 33.3".
6. **The end.** "<name> wins the event." in gold, "FINAL STANDINGS", the table, and Main Menu.
7. **Builder between rounds.** It opens on your last deck (30 cards, leader and base kept), the clock reads "Next round in
   m:ss", and Ready (after the "Lock in this deck?" confirm) takes you back to the room. In a Draft pod the Ready line says
   "Everyone else is Ready".
8. **All three engines** at both widths look the same; nothing overlaps the chat panel.
