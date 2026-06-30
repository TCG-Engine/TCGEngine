# ASH_135 The Darksaber — "Attached unit gains the Mandalorian trait." A friendly ASH_113 Mandalorian
# Flagship ("+1/+0 for each OTHER friendly Mandalorian unit") counts the Darksaber-wearing SOR_046 (now
# Mandalorian) and gets +1 → 5 power (base 4). SOR_046 is normally Rebel/Trooper, so without the grant
# ASH_113 would stay at 4.
## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1SpaceArena: ASH_113:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_135
## EXPECT
P1SPACEARENAUNIT:0:POWER:5
