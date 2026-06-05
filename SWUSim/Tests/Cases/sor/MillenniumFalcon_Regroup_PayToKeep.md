# SOR_193 Millennium Falcon — regroup trigger:
# "When you ready cards during the regroup phase: Either pay [1 resource] or return this unit
#  to her owner's hand."
# Falcon is on the board. Both players pass → regroup. During the Ready step the Falcon trigger
# asks the controller to pay 1 resource (YES) or bounce (NO). Paying keeps the Falcon and
# exhausts 1 resource: 2 resources → 2 total, 1 ready / 1 exhausted.
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
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_193
P1RESCOUNT:2
P1RESAVAILABLE:1
