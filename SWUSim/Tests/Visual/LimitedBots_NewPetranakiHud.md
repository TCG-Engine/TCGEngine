# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It has no GIVEN/WHEN/EXPECT. It checks the Limited room panel's bot seats (SharedUI/Render/WaitingRoom.php #lt-root)
# and the pick screen's pod row, not a gamestate.
#
# VISUAL CHECK — bots in a Limited room, in the "New Petranaki HUD" style
#
#   What changed (Plan 5, docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md §3.7): the host fills
#   any Draft seat, or Sealed's second, with a bot during setup. Client: SWUSim/Limited/ui/limited-room.js (seatList,
#   "Add a bot", the ✕), SWUSim/Limited/ui/limited-draft.js (pod pills). Data: SWUSim/LimitedAction.php addBot /
#   removeBot, the poll's seats[].bot. Styles: SharedUI/Sites/SWUSim/css/swusim-menu-2.css "Bots (Plan 5)".
#
#   The bugs it guards: a guest seeing bot controls; "Add a bot" enabled in a full room; a bot shown "Disconnected"
#   (it never polls); a bot's seat missing its "Bot" mark anywhere players read the pod.

## HOW TO RUN

Automated half (Chromium / Firefox / WebKit, 1440px and 390px):
    cd DevTools/ui-harness && node swusim-limited-room-xbrowser.mjs [BASE] [SHOTS_DIR]   (section "bots")
Fixtures: SWUSim/DevTools/tests/limited_fixture.php?phase=setup&mode=sealed&bots=1, …&mode=draft&players=3&bots=2.
Whole events with bots: node swusim-limited-draft-sim.mjs --players 1 --bots 7 --swiss (SWUSim/Tests/Visual/LimitedDraftSim.md).

## WHAT TO LOOK AT (in order; each one can fail)

1. **Host, setup.** Each bot seat reads "Arenabot Alpha / Beta / …" with a gold "BOT" chip and a ✕ at the row's right
   end (44px, "Remove Arenabot Alpha" to a screen reader). Under the seats, "Add a bot" — disabled once the room is full
   (Sealed: host + one bot; Draft: 8 seats).
2. **Draft.** Host + 2 bots is enough: "Start Draft" is enabled.
3. **Guest.** The bot seats and their "BOT" chip show; there is no "Add a bot" and no ✕.
4. **A bot is never "Disconnected"** — its seat shows only Host / BOT / Ready chips.
5. **Pick screen.** In the pod row, each bot's pill carries a small gold "BOT" tag after its name.
6. **Rounds and standings.** A bot's name in the tables and standings carries the same small "BOT" tag.
7. **390px.** No sideways scroll; the ✕ stays on the seat row; "Add a bot" is full width.
