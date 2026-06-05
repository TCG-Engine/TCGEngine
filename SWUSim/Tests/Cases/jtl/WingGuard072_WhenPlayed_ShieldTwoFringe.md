# JTL_072 Wing Guard Security Team — Sentinel + When Played: Give a Shield token to each of up to 2
# Fringe units. P1 shields both Fringe units: JTL_072 itself (Fringe Trooper) and JTL_062 (Fringe).

## GIVEN
P1LeaderBase: JTL_004/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_072
WithP1Resources: 6
WithP1SpaceArena: JTL_062:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&mySpaceArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_072
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_062
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
