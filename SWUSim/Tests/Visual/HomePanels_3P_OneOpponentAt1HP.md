# VISUAL CHECK — Twin Suns 3P: one opponent on 1 HP, two Battle Droids on every board

Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
Load it by hand in the Test Schema Editor, then open as **P1**. Desktop layout, 1700x1100.

http://localhost:3400/TCGEngine/NextTurn.php?folderPath=SWUSim&gameName=&lt;id&gt;&playerID=1

## WHY THIS FILE EXISTS

Two things this board makes checkable that a healthy one cannot:

* **A base one point from dead.** P3's base carries **29 damage on a 30 HP base** — the largest damage
  number the tile ever has to render, and the state right before the seat is eliminated. The home tile's
  damage token is sized for this; a token that clips or overflows at 29 is the bug.
* **An IDENTICAL unit on every board.** Every seat has exactly two TWI_T01 Battle Droids (1/1 tokens) and
  nothing else, so the three mini-boards differ ONLY by base damage. Any other difference between the
  tiles — a card drawn at a different size, a row offset, a missing power/HP pip — is a rendering bug,
  not the fixture. A board where each seat has different units cannot show that.

It is also the board the "show an eliminated seat as defeated" question is about: P3 is one point from
gone, so this is the last frame before whatever defeated-state we choose has to render.
See the OTMTCGE memory `twinsuns-home-panels-survive-elimination`.

## WHAT TO LOOK AT

* **The home strip along the top** carries a tile per LIVE opponent — here two, P2 and P3.
* **P3's base damage reads 29** and P2's reads 0. Both are on the base thumbnail, centred, and 29 must sit
  inside the token without clipping (double digits are the case that overflows a token drawn for one).
* **Two Battle Droids per tile**, both 1/1, in the GROUND row. Space rows are empty on every seat.
* **P1's own board** (yours, below) also has exactly two Battle Droids — the fixture is symmetric, so your
  board and the tiles should agree card-for-card.
* ⚠ The tiles must be the SAME SIZE as each other. Two live opponents is the normal 3P shape; compare
  against `HomePanels_SurviveElimination_TwinSuns.md`, where one opponent has been eliminated and the
  single remaining tile stretches to the full strip width.

## VERIFIED

Rendered and eyeballed 2026-09-25 in **Chromium, Firefox and WebKit** at 1700x1100, as P1:
two tiles (P2, P3), P3's **29** in a red token on the base thumbnail — legible, centred, not clipped —
and two Battle Droids with red 1 / blue 1 pips in each ground row. All three engines identical.
Every chip reads `RES 0/0 · HAND 0 · DECK 0 · DISCARD 0` on both tiles: the fixture seeds no resources,
hand or deck, so the chips are a same-on-every-tile control rather than distinct values.

## THE DEFEATED STATE (owner ruling 2026-09-25)

This board is also the BEFORE frame for elimination. To see the AFTER, mark the near-dead seats dead:

```
php SWUSim/DevTools/set-live-seats.php <id> 12
```
⚠ Then **clone the game to a fresh id and open THAT** — the web process caches a game as created, so a
CLI edit to LiveSeats is invisible to the page until the id changes.

A defeated seat KEEPS its panel: the tile stays in the strip, its **units are gone entirely**, and the
whole tile — playmat art, leader, base, chips — renders **greyscale**, with Zoom In still available and
opening that seat's emptied, greyed board. Live tiles stay in full colour, so alive vs dead reads at a
glance. Cover: `DevTools/ui-harness/swusim-home-panels-survive-elimination.mjs`.

## GIVEN
#// Three seats, all dressed (a far seat with no base/leader pays the full aspect penalty and its plays
#// become silent no-ops — see the CommonSetup3P note in SchemaTestRunner).
#// ⚠ Base damage for seats 1-2 goes through myBaseDamage/theirBaseDamage: `myBase:ID:damage` silently
#// drops the damage. Far seats take it inline on WithP3Base.
CommonSetup3P: rrk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP3Base: SOR_026:29
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP2GroundArena: TWI_T01:1:0
WithP2GroundArena: TWI_T01:1:0
WithP3GroundArena: TWI_T01:1:0
WithP3GroundArena: TWI_T01:1:0

## WHEN

## EXPECT
SEATCOUNT:3
P3BASEDMG:29
P1GROUNDARENACOUNT:2
P2GROUNDARENACOUNT:2
P3GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P3GROUNDARENAUNIT:1:CARDID:TWI_T01
