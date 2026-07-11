# SHD_101 Adelphi Patrol Wing — without the initiative (P2 holds it), the attacking SOR_237 gets no +2 and
# deals its printed 2 to the base.

## GIVEN
CommonSetup: ggw/ggw/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_101
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P2BASEDMG:2
