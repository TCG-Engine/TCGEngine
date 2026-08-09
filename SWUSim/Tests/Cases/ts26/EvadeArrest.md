# ExhaustNonUniqueUnits
#// TS26_82 Evade Arrest (Event, cost 3, Cunning) — Exhaust any number of non-unique units. Both
#// non-unique units chosen are exhausted.
## GIVEN
CommonSetup: yyk/rrk/{myResources:3;handCardIds:TS26_82}
WithP1GroundArena: [SEC_080:1:0 SOR_095:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:EXHAUSTED

---

# OffersEveryNonUniqueUnitOnEitherSide
#// TS26_82 Evade Arrest — "exhaust any number of NON-UNIQUE units", with no friendly-only clause. P1's
#// non-unique SEC_080 and P2's SOR_095 are both offered; P1's unique Mother Talzin (TS26_26) is not.

## GIVEN
CommonSetup: yyk/rrk/{myResources:3;handCardIds:TS26_82}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SEC_080:1:0 TS26_26:1:0]
WithP2GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# ChoosingZeroUnitsExhaustsNothing
#// TS26_82 Evade Arrest — "ANY NUMBER" includes none. Declining leaves both units ready; the event still
#// resolves into the discard.

## GIVEN
CommonSetup: yyk/rrk/{myResources:3;handCardIds:TS26_82}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SEC_080:1:0 SOR_095:1:0]
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:READY
P1DISCARDCOUNT:1

---

# ExhaustingEVERYNonUniqueUnitOnTheBoard
#// TS26_82 Evade Arrest — "any number" has no upper bound short of the whole legal pool. Naming both of
#// P1's units and P2's leaves all three exhausted.

## GIVEN
CommonSetup: yyk/rrk/{myResources:3;handCardIds:TS26_82}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SEC_080:1:0 SOR_095:1:0]
WithP2GroundArena: SOR_128:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1&theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:EXHAUSTED
P2GROUNDARENAUNIT:0:EXHAUSTED
