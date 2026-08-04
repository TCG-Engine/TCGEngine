# NoFriendlyDefeated_CostsFullFive
#// IC27_022 Moff Gideon (Cold Calling) — 5 cost, 3/6, Vigilance+Villainy, Ground, Imperial/Official.
#// Text: "If a friendly unit was defeated this phase, this unit costs [2 resources] less to play."
#// A passive cost modifier with NO trigger stub. Baseline: nothing has died, so 4 resources is one
#// short and the play silently no-ops (he stays in hand).

## GIVEN
CommonSetup: bbk/bbk/{myResources:4;myhandCardIds:IC27_022}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1RESAVAILABLE:4

---

# FriendlyDefeatedThisPhase_CostsThree
#// THE POSITIVE: a friendly unit trades into a bigger body, then Gideon comes down for 3 — the same
#// 4 resources that were insufficient above now leave 1 spare.

## GIVEN
CommonSetup: bbk/bbk/{myResources:4;myhandCardIds:IC27_022}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:IC27_022
P1HANDCOUNT:0
P1RESAVAILABLE:1

---

# EnemyDefeatedDoesNotEnableTheDiscount
#// THE LOAD-BEARING NEGATIVE on "friendly": an ENEMY unit dying this phase must NOT reduce the cost.
#// P1's 3/7 wall kills a 3/1 Stormtrooper and survives, so only an enemy died — Gideon still costs 5
#// and 4 resources cannot pay for him.

## GIVEN
CommonSetup: bbk/bbk/{myResources:4;myhandCardIds:IC27_022}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1HANDCOUNT:1
P1RESAVAILABLE:4

---

# DiscountResetsNextPhase
#// DURATION EDGE: "this phase" must expire. A friendly unit dies, the round turns over, and Gideon is
#// back to full price — 4 resources no longer pay for him.

## GIVEN
CommonSetup: bbk/bbk/{myResources:4;myhandCardIds:IC27_022}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1RESAVAILABLE:4
