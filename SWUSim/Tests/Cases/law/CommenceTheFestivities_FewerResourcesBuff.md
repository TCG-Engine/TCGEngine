# LAW_202 Commence the Festivities (Aggression event, cost 1) — "Attack with a unit. It gains Saboteur
# for this attack. If you control fewer resources than an opponent, it gets +2/+0 for this attack."
# P1 controls 1 resource vs P2's 3 -> SEC_080 (power 3) attacks the base for 3+2 = 5.

## GIVEN
CommonSetup: rrk/bgw/{myResources:1;theirResources:3}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_202

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:5
