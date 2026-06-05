# SOR_193 Millennium Falcon — regroup trigger, choose to bounce:
# "Either pay [1 resource] or return this unit to her owner's hand."
# Falcon is on the board. During the Ready step the controller declines to pay (NO), so the
# Falcon returns to its owner's hand. Resources are untouched.
# Hand ends at 3: 0 starting + 2 drawn in the Draw step + the returned Falcon.
#
# NOTE (phase-crossing): both players must answer the Resource-step MZMAYCHOOSE (ResourcePass)
# before the cycle reaches the Ready step where the Falcon trigger fires.

## GIVEN
P1LeaderBase: SOR_014/SOR_024
P2LeaderBase: SOR_007/SOR_024
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_193:1:0
WithP1Resources: 2
P1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AnswerDecision:NO

## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:3
P1RESCOUNT:2
P1RESAVAILABLE:2
