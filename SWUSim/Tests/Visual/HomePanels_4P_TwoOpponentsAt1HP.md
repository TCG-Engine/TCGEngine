# VISUAL CHECK — Twin Suns 4P: TWO opponents on 1 HP, two Battle Droids on every board

Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
Load it by hand in the Test Schema Editor, then open as **P1**. Desktop layout, 1700x1100.

http://localhost:3400/TCGEngine/NextTurn.php?folderPath=SWUSim&gameName=&lt;id&gt;&playerID=1

## WHY THIS FILE EXISTS

The 4-seat sibling of `HomePanels_3P_OneOpponentAt1HP.md`, and the case that discriminates hardest:

* **THREE tiles at once.** Four seats is the widest the home strip ever gets, so this is where tiles are
  most likely to be squeezed, wrap, or lose a row. Three tiles must fit side by side without the base
  damage token, the zone chips or the Zoom In button colliding.
* **TWO bases one point from dead.** P3 and P4 both carry **29 damage on a 30 HP base**; P2 is untouched
  at 0. Two near-dead seats on screen together is the state right before a 4P game narrows to two live
  seats — the transition that must NOT zoom into a 1v1 any more
  (`HomePanels_SurviveElimination_TwinSuns.md`, memory `twinsuns-home-panels-survive-elimination`).
* **An IDENTICAL unit on every board.** Every seat has exactly two TWI_T01 Battle Droids (1/1 tokens) and
  nothing else, so the four mini-boards differ ONLY by base damage. Any other difference between the
  tiles is a rendering bug, not the fixture.

⚠ A 4-seat board is where seat handling has drifted before — several bugs were invisible at 2 and 3 seats
and only showed at 4 (memory `four-seat-sections-must-discriminate`). Prefer this file over the 3P one
when checking a change to the strip.

## WHAT TO LOOK AT

* **Three tiles in the top strip** — P2, P3, P4 — all the SAME size, none wrapped to a second row.
* **P3 and P4 both read 29 base damage; P2 reads 0.** The two 29s must render identically: same token,
  same position, no clipping. Two tiles showing the same number is the cheapest way to catch a tile whose
  layout depends on its index in the strip.
* **Two Battle Droids per tile**, both 1/1, in the GROUND row. Space rows empty on every seat.
* **P1's own board** below also has exactly two Battle Droids — the fixture is symmetric, so your board
  and all three tiles should agree card-for-card.
* Zone chips on every tile read the same values (`RES`, `HAND`, `DECK`, `DISCARD`); a chip that differs
  between tiles here is reading another seat's block — see `HomeTile_ZoneCounts_AllDistinct.md`, which is
  the fixture built to catch exactly that.

## VERIFIED

Rendered and eyeballed 2026-09-25 in **Chromium, Firefox and WebKit** at 1700x1100, as P1:
**three tiles side by side, none wrapped**, all the same size; P3 and P4 each show **29** in identical red
tokens in the same position while P2 shows none; two Battle Droids with red 1 / blue 1 pips in every
ground row; space rows empty. All three engines identical.
Every chip reads `RES 0/0 · HAND 0 · DECK 0 · DISCARD 0` on all three tiles: the fixture seeds no
resources, hand or deck, so the chips are a same-on-every-tile control rather than distinct values.

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
#// Four seats, all dressed (an undressed far seat pays the full aspect penalty and its plays become
#// silent no-ops — see the CommonSetup4P note in SchemaTestRunner). CommonSetup4P defaults SeatOrder and
#// LiveSeats to "1234", so every seat is alive: nobody here is eliminated, they are merely nearly dead.
#// ⚠ Base damage for seats 1-2 goes through myBaseDamage/theirBaseDamage: `myBase:ID:damage` silently
#// drops the damage. Far seats take it inline on WithP3Base / WithP4Base.
CommonSetup4P: rrk/bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP3Base: SOR_026:29
WithP4Base: SOR_026:29
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP2GroundArena: TWI_T01:1:0
WithP2GroundArena: TWI_T01:1:0
WithP3GroundArena: TWI_T01:1:0
WithP3GroundArena: TWI_T01:1:0
WithP4GroundArena: TWI_T01:1:0
WithP4GroundArena: TWI_T01:1:0

## WHEN

## EXPECT
SEATCOUNT:4
P2BASEDMG:0
P3BASEDMG:29
P4BASEDMG:29
P1GROUNDARENACOUNT:2
P2GROUNDARENACOUNT:2
P3GROUNDARENACOUNT:2
P4GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P4GROUNDARENAUNIT:1:CARDID:TWI_T01
