# LAW_202 Commence the Festivities — if you do NOT control fewer resources than the opponent, no +2/+0.
# P1 controls 3 vs P2's 1 -> SEC_080 attacks the base for just 3.

## GIVEN
CommonSetup: rrk/bgw/{myResources:3;theirResources:1}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_202

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:3
