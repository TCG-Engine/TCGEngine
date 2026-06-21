# SEC_195 Arrest (Event, cost 2, Cunning/Villainy)
#   "Your base captures an enemy non-leader unit. At the start of the regroup phase, its owner rescues it."
# This test: the capture. P1 plays Arrest and captures P2's SOR_095 — it leaves play (removed; stored on
# P1's base via a GlobalEffects flag since bases have no Subcards). P2's arena is now empty.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SEC_195
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
