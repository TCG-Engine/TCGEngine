# ExhaustThenAttackPlus3
#// SEC_228 Clever Gambit (event, cost 1) — Exhaust a friendly unit. If you do, attack with another unit.
#//   It gets +3/+0 for this attack. P1 exhausts SEC_041 (idx 0), then SEC_042 (power 2 → 5) attacks P2's
#//   base for 5.

## GIVEN
CommonSetup: yyk/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP1GroundArena: SEC_042:1:0
WithP1Hand: SEC_228

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:EXHAUSTED
