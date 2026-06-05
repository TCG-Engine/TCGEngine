# JTL_070 U-Wing Lander — When Played: Give 3 Experience tokens to this unit. The 2/2 lander gains
# +3/+3 (5/5) and carries 3 token upgrades. (The complete-attack move-upgrade rider is implemented with
# the Phase 16/18 upgrade-move work.)

## GIVEN
P1LeaderBase: JTL_004/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_070
WithP1Resources: 5

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_070
P1SPACEARENAUNIT:0:POWER:5
P1SPACEARENAUNIT:0:HP:5
P1SPACEARENAUNIT:0:UPGRADECOUNT:3
