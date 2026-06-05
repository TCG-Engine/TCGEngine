# JTL_175 System Shock (event) — Defeat a non-leader upgrade attached to a unit. If you do, deal 1 to
# that unit. P1 defeats SOR_120 on the enemy SOR_046 and then deals 1 to SOR_046.

## GIVEN
P1LeaderBase: JTL_012/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_175
WithP1Resources: 1
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:1
