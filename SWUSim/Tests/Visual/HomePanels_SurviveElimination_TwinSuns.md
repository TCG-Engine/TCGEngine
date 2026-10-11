# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It has no GIVEN/WHEN/EXPECT. The board comes from the CLI fixture below.
# (It could become one: WithEliminatedSeats: now expresses a dead seat. Not converted yet.)
#
# VISUAL CHECK — Twin Suns: the Home panels SURVIVE narrowing to two live seats

Owner ruling 2026-09-25: "remove the experience in Twin Suns when a 3P game goes down to a 2P game and it
zooms in to the 2 remaining players. people actually like the Home panels." The **4P → 3P shift is the
baseline**: eliminated seats simply stop being tiled and the multi-seat chrome stays.

## Fixture

A 3-seat game with seat 1 eliminated, opened as a SURVIVOR (seat 2):

```
docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 sh -c \
  'rm -rf SWUSim/Games/9846531 && cp -r SWUSim/Games/846502 SWUSim/Games/9846531 && \
   php -d xdebug.mode=off SWUSim/DevTools/set-live-seats.php 9846531 23'
```
http://localhost:3400/TCGEngine/NextTurn.php?folderPath=SWUSim&gameName=9846531&playerID=2

⚠ 846502 is a FINISHED game, so the end-game overlay sits on top of the board. It does not hide the home
strip, which is what this case is about — but do not read the overlay as part of the layout. Point this at
any in-progress 3-seat game for a cleaner shot.

## WHAT TO LOOK AT — the strip along the TOP (`#swuHomeStrips`)

* **It must be there at all.** Before this change the view list was thrown away the moment a game narrowed
  to two live seats (`if (seats.length <= 2) return [];`), so the strip, the Home view and the Zoom In
  button all vanished and the board re-framed itself as a plain 1v1. Their PRESENCE is the check.
* **Exactly ONE tile, and it is the LIVE opponent (P3).** The eliminated seat 1 gets no tile — the same
  thing that already happens at 4P → 3P, where the dead seat drops out of `opps`.
* The tile carries the full mini-board: `P3` label, leader + base thumbnails with their damage tokens,
  the zone chips (`RES 8/8 · HAND 2 · DECK 57 · DISCARD 8`), the TURN / INITIATIVE badges, a **Zoom In**
  button, and the ground/space arena rows beneath.
* ⚠ **With only one opponent the tile spans the full strip width** rather than sitting beside siblings.
  That is expected — the strip is a flex row and there is one child. Cards stay left-aligned, so the right
  side of each arena box reads as empty space, not as a broken box.

## The CONTROL — a genuine 1v1 must be unchanged

Open any 2-player game. It must have **no** home strip, no Home view and no Zoom In: only a game that
SEATED 3+ keeps the chrome. If a plain 1v1 grows a Home panel, the `seatedEver.length <= 2` guard in
`swuBuildViews()` has been lost.

## Automated cover

`DevTools/ui-harness/swusim-home-panels-survive-elimination.mjs` asserts all of the above in Chromium,
Firefox and WebKit (24 checks), including the 1v1 control:

```
cd DevTools/ui-harness && node swusim-home-panels-survive-elimination.mjs 846502 1287222
```
