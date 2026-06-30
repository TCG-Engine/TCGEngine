# JTL_260 Death Star Plans — "Attached unit gains: 'The first unit you play each round costs 2 resources
# less.'" P1 controls SOR_046 bearing Death Star Plans, then plays JTL_099 (cost 3) which costs 1.
# Resource check: 10 − 1 = 9 ready left (would be 7 without the discount).

## GIVEN
CommonSetup: ggw/rrk/{myResources:10;handCardIds:JTL_099}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:JTL_260

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:9
