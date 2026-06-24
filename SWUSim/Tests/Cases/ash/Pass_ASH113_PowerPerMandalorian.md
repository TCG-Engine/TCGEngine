# ASH_113 Mandalorian Flagship (Space, 4/8) — gets +1/+0 for each other friendly Mandalorian unit. With
# two friendly Mandalorian units (ASH_216, ASH_064), the Flagship is at power 4 + 2 = 6.
## GIVEN
CommonSetup: ggw/ggk
WithP1SpaceArena: ASH_113:1:0
WithP1GroundArena: ASH_216:1:0
WithP1GroundArena: ASH_064:1:0
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1SPACEARENAUNIT:0:CARDID:ASH_113
P1SPACEARENAUNIT:0:POWER:6
