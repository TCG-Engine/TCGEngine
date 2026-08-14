# ExhaustEnemyShieldFriendly
#// SOR_218 Asteroid Sanctuary (Event) — Exhaust an enemy unit. Give a Shield token to a
#// friendly unit that costs 3 or less. The lone enemy unit is exhausted and the lone friendly
#// unit (Battlefield Marine, cost 2 ≤ 3) gains a Shield. Both effects auto-resolve.
#// COVERAGE: offer=Offer_ExhaustPoolIsEnemyUnitsOnly + Offer_ShieldPoolIsFriendlyCostThreeOrLess
#//           (both pending SELECTABLEEXACT) · reqboundary=ChoosesBothTargets (the shield offer is
#//           built after the exhaust answer, each answer a separate request) · control=N/A (both
#//           halves scope by live controller; the enemy-only/friendly-only offers pin the seat
#//           split) · boundary pair=Offer_ShieldPoolIsFriendlyCostThreeOrLess (cost 3 IN the pool,
#//           cost 4 out) · decline=N/A (both clauses mandatory; NoEnemyUnits_ShieldOnly /
#//           NoFriendlyUnits_ExhaustOnly / NoTargets_PlaysToNoEffect pin the fizzle halves)

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_218
WithP1GroundArena: SEC_080:1:0    # friendly, cost 2 (≤3) — Shield recipient
WithP2GroundArena: SEC_080:1:0    # enemy — exhaust target

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# Offer_ExhaustPoolIsEnemyUnitsOnly
#// Intended: the first target pool is exactly the ENEMY units — friendly units are never
#// offered for the exhaust half. Two enemy units keep the choice interactive; the decision is
#// left pending so the exact pool can be inspected.

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_218
WithP1GroundArena: SOR_128:1:0    # friendly (cost 1) — must NOT be in the exhaust pool
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# Offer_ShieldPoolIsFriendlyCostThreeOrLess
#// Intended: after the exhaust pick, the second pool is the FRIENDLY units that cost 3 or
#// less. Cost boundary: Death Star Stormtrooper (1) and Wilderness Fighter (3) are IN,
#// Consular Security Force (4) is OUT. Left pending for inspection.

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_218
WithP1GroundArena: SOR_128:1:0    # cost 1 — eligible
WithP1GroundArena: SOR_064:1:0    # cost 3 — eligible (boundary: "3 or less" includes 3)
WithP1GroundArena: SOR_046:1:0    # cost 4 — excluded
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# ChoosesBothTargets
#// Full resolution of the two picks above: the chosen enemy is exhausted (the other enemy
#// stays ready) and the chosen cost-1 friendly gains the Shield (the others get nothing).

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_218
WithP1GroundArena: SOR_128:1:0
WithP1GroundArena: SOR_064:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:READY
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0
P1GROUNDARENAUNIT:2:SHIELDCOUNT:0
P1NODECISION

---

# NoFriendlyUnits_ExhaustOnly
#// Intended: with no friendly units at all, the event can still be played for the exhaust
#// half alone — the shield half fizzles independently and raises no prompt.

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_218
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:READY
P1NODECISION

---

# NoEnemyUnits_ShieldOnly
#// Intended: with no enemy units, the exhaust half fizzles independently (no prompt) and the
#// event still shields a friendly unit that costs 3 or less.

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_218
WithP1GroundArena: SOR_128:1:0
WithP1GroundArena: SOR_064:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0
P1NODECISION

---

# NoTargets_PlaysToNoEffect
#// Intended: with no units in play at all, the event can still be played to no effect — it
#// pays its cost, raises no prompt, and lands in the discard.

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_218

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1RESAVAILABLE:2
P1NODECISION
