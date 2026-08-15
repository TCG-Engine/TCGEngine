# PlaysUnitDiscountedThenDeals4
#// TS26_32 Reckless Landing (Event, cost 2, Aggression/Cunning) — Play a unit from your hand. It costs 4
#// resources less. Deal 4 damage to it.
#// P1 plays the event, then the only playable hand unit (JTL_069 Munificent Frigate, 4/7 space, cost 5;
#// −4 = 3 after the Vigilance off-aspect penalty is budgeted) is chosen, enters play and takes 4 damage.
#// (Extra answer since 2026-08-14: this "you may" offer no longer auto-resolves a lone target.)
## GIVEN
CommonSetup: ryk/rrk/{myResources:7}
WithP1Hand: [TS26_32 JTL_069]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1
## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:DAMAGE:4

---

# NoPlayableUnitInHand_ResolvesCleanly
#// TS26_32 Reckless Landing — "Play a unit from your hand" with no unit in hand. The event still resolves
#// into the discard, the hand empties, and no half-built decision is left pending.

## GIVEN
CommonSetup: ryk/rrk/{myResources:7}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TS26_32
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1HANDCOUNT:0
P1NODECISION

---

# TheDamageLandsBEFORETheShieldedTokenArrives
#// TS26_32 Reckless Landing — the 4 damage is dealt as the unit enters, ahead of its own entry triggers.
#// Sorcerers of Tund (LOF_214, 6/6, Shielded) is played for 2 (6 - 4) and ends up holding BOTH: 4 damage
#// AND its Shield token, because the Shield was not there yet to absorb the hit. 7 - 2 - 2 = 3 resources.
#// Discriminating: if the damage arrived after the entry triggers, the Shield would eat it and the unit
#// would show 0 damage and no upgrade.
#// (Extra answer since 2026-08-14: this "you may" offer no longer auto-resolves a lone target.)

## GIVEN
CommonSetup: ryk/rrk/{myResources:7}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [TS26_32 LOF_214]
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1RESAVAILABLE:3

---

# AFourHPUnitIsDefeatedOnArrival
#// TS26_32 Reckless Landing — the 4 damage kills anything with 4 or less HP outright. TS26_65 Bo-Katan
#// Kryze (1/4) is played for 0 (2 - 4, floored) and goes straight to the discard, leaving the arena empty
#// and both cards in the discard pile.
#// (Extra answer since 2026-08-14: this "you may" offer no longer auto-resolves a lone target.)

## GIVEN
CommonSetup: ryk/rrk/{myResources:7}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [TS26_32 TS26_65]
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2

---

# ChoosingNothingLeavesTheUnitsInHand
#// TS26_32 Reckless Landing — the play is optional even with legal choices available. Declining leaves
#// both units in hand, nothing on the board, and only the event's own 2 resources spent.

## GIVEN
CommonSetup: ryk/rrk/{myResources:7}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [TS26_32 LOF_214 TS26_65]
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:2
P1RESAVAILABLE:5

---

# Decline_SingleTarget_NoUnitPlayed
#// TS26_32 Reckless Landing — with exactly ONE playable unit in hand the "play a unit" offer is still
#// declinable. P1 answers "-": JTL_069 stays in hand, nothing enters the arena and nothing takes 4
#// damage, but the event was still played — it is in the discard and its own 2 resources are spent
#// (7 - 2 = 5).
#// Pairs with ChoosingNothingLeavesTheUnitsInHand, which declines with TWO legal choices; this branch
#// only became testable on 2026-08-14, when a lone-target optional offer stopped auto-resolving.

## GIVEN
CommonSetup: ryk/rrk/{myResources:7}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [TS26_32 JTL_069]
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:1
P1DISCARDCOUNT:1
P1RESAVAILABLE:5
P1NODECISION
