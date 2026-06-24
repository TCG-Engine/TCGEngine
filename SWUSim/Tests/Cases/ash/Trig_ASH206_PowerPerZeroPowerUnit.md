# ASH_206 Kelleran Beq (Ground, 3/5, Ambush) — gets +1/+0 for each OTHER unit (friendly and enemy) with 0
# power. With a friendly ASH_072 (0 power) and an enemy ASH_073 (0 power) on the board, Kelleran is at
# power 3 + 2 = 5.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: ASH_206:1:0
WithP1GroundArena: ASH_072:1:0
WithP2GroundArena: ASH_073:1:0
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_206
P1GROUNDARENAUNIT:0:POWER:5
