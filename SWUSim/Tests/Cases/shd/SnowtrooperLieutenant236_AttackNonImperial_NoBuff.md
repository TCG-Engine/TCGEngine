# SHD_236 Snowtrooper Lieutenant — attacking with a NON-Imperial unit (SOR_095, Rebel) grants no +2, so the
# base takes its printed 3.

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_236
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:3
