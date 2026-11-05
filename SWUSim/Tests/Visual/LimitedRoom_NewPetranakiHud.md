# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It has no GIVEN/WHEN/EXPECT. It checks the WAITING ROOM page's Limited panel (#lt-root), not a gamestate.
#
# VISUAL CHECK — the SWUSim Limited (Sealed) room, in the "New Petranaki HUD" style
#
#   What changed: a Limited room (format `limited`, docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md)
#   draws its own panel in the waiting room — the host's settings, the two seats, the 30-minute build clock, the kick
#   prompt and the result — in place of the seat tiles and the deck bar, because nobody brings a deck: each player builds
#   one from the packs the room opens. Markup: SWUSim/Limited/ui/limited-room.js (mounted by SharedUI/Render/WaitingRoom.php
#   renderLimited). Styles: SharedUI/Sites/SWUSim/css/swusim-menu-2.css "Limited room panel", scoped
#   `body:has(#wr-root) .wr-panel`, every class lt- prefixed. The main menu's PvP dialog gains "Create a Limited Room".
#
#   The bug it guards: a Limited seat that sees the deck bar would paste a Constructed list, and a host who cannot see why
#   Start is greyed would never start. Also the redraw signature (WaitingRoom.php poll): before `r.limited` was in it, the
#   host's settings errors never reached the screen.

## HOW TO RUN

Automated half (Chromium / Firefox / WebKit, 1440px and 390px):
    cd DevTools/ui-harness && node swusim-limited-room-xbrowser.mjs [BASE] [SHOTS_DIR]
Fixture: SWUSim/DevTools/tests/limited_fixture.php?phase=setup|building|playing|sideboard|done (moderators only on prod;
open on a local dev box) — a two-seat room, claudebot1 hosting, claudebot2 joined; `building` opens 6 SOR packs each.
Then by eye: Main Menu → PvP → Create a Limited Room as claudebot1; open the invite link in a private window as claudebot2.

## WHAT TO LOOK AT (in order; each one can fail)

1. **Menu.** The PvP dialog's button row has "Create a Limited Room" (steel, beside High Ground). It is there whatever pool
   or deck is chosen. Clicking it lands in the waiting room with the Limited panel — no "Choose a deck" error.
2. **Head.** "LIMITED" title, Leave top-right, the invite line. "SEALED" + a gold "SETTING UP" beside it.
3. **Host settings.** Rows: Mode (fixed "Sealed"), Match length (Bo1 / Bo3 segmented), Packs per player (select 6–16),
   Packs (Single Set / Host's Choice / Chaos segmented). Labels small, spaced uppercase, left column.
4. **Single Set.** One Set select listing SOR SHD TWI JTL LOF SEC LAW ASH HMW only — never IBH, TS26, IC27.
5. **Host's Choice.** One select per pack, each with a small square 🔀 at its right (NOT stretched across the row — the
   desktop grid once stretched it). "Shuffle all" and "Reset" under the list. 🔀 changes only that row, and the choice
   is still there after the next poll.
6. **Chaos.** A 3-across grid of set checkboxes. One ticked → the red line "Chaos Sealed needs at least 2 different sets."
   and Start greyed; a second ticked → the line goes and Start lights.
7. **Guest.** The same rows, every control disabled; no Start; "The host chooses the settings and starts the event."
8. **Seats.** Two sunken rows; yours has the gold left rim; HOST / SUBMITTED / READY (green) / DISCONNECTED chips.
9. **Building.** "DECKBUILDING ENDS IN" + a large m:ss clock that counts down in place (turns red under a minute), "Your
   packs: SOR · …", and a gold-rimmed "Open deckbuilder" → SWUSim/LimitedBuilder.php?lobbyID=…
10. **Kick prompt** (a seat silent 30 s, or no deck at the deadline): a red-tinted strip "Kick <name>?" with Kick and
    "Wait another 20 seconds".
11. **Done.** "<winner> won 2–1." (or "<name> was removed — <winner> wins.") in gold, and a Main Menu button.
12. **Everywhere.** No deck bar, no seat tiles; every button ≥44px; no horizontal scroll at 390px.

## NEIGHBOURING INVARIANTS (must not change)

- A normal private room and a High Ground room render exactly as before (swusim-high-ground-xbrowser.mjs,
  swusim-site-pages-hud-xbrowser.mjs stay green). `#lt-root` is empty and hidden for them.
- Other sims' waiting rooms load no Limited script (it is added for rootName SWUSim only).

## CROSS-BROWSER / MOBILE

Chromium, Firefox and WebKit at 1440px and 390px, every state above. The page is responsive CSS (no separate mobile
markup), so `?swuLayout=mobile` changes nothing here; the 390px run is the phone check. Native touch on a real iPhone
(Safari) is the owner's manual check.
