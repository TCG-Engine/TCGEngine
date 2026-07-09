# SHD_037 Supreme Leader Snoke (8-cost ground) — "Each enemy non-leader unit gets -2/-2." Guard: the enemy
# SOR_046 (3/7) is reduced to 1/5 while Snoke is in play.

## GIVEN
CommonSetup: bbk/bbk
P1OnlyActions: true
WithP1GroundArena: SHD_037:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:5
