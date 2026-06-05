# JTL_163 AT-DP Occupier — This unit costs 1 resource less to play for each damaged ground unit. With
# two damaged ground units in play (SOR_095, SOR_046), the cost-4 Occupier plays for 4-2=2, consuming
# exactly 2 resources.

## GIVEN
P1LeaderBase: JTL_012/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_163
WithP1Resources: 2
WithP1GroundArena: SOR_095:1:1
WithP2GroundArena: SOR_046:1:2

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:JTL_163
P1RESAVAILABLE:0
