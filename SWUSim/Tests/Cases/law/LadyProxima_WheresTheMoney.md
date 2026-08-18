# ActionCreateCredit
#// LAW_235 Lady Proxima (1/5 ground, Underworld) — "Action [Exhaust]: Create a Credit token." Using the
#// action creates 1 Credit token and exhausts her.

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: LAW_235:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1CREDITCOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# ActionUnusableWhenExhausted
#// LAW_235 Lady Proxima — the Credit action costs [Exhaust], so an already-exhausted Lady Proxima
#// cannot use it. No Credit is created and she stays exhausted.

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: LAW_235:0:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1CREDITCOUNT:0
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# ActionUsableAgainOnceSheReadies
#// LAW_235 Lady Proxima — the only gate on the Credit action is the [Exhaust] cost, not a once-per-round
#// limit. She creates a Credit, the regroup readies her, and she creates a second one in the next action
#// phase. Both decks are seeded so the regroup draws add no CR 6.1 empty-deck damage.

## GIVEN
CommonSetup: yyk/rrk/{}
WithP1GroundArena: LAW_235:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1CREDITCOUNT:2
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# P2Seat_TheCreditGoesToTheUNITSController
#// LAW_235 Lady Proxima — the Credit belongs to whoever controls her and uses the action. P2 fields her
#// and uses it: P2 holds 1 Credit, P1 none. The existing sections are P1-only and cannot see a hardcoded
#// seat.

## GIVEN
CommonSetup: rrk/yyk/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2GroundArena: LAW_235:1:0

## WHEN
- P2>UseUnitAbility:myGroundArena-0

## EXPECT
P2CREDITCOUNT:1
P1CREDITCOUNT:0
P2GROUNDARENAUNIT:0:EXHAUSTED
