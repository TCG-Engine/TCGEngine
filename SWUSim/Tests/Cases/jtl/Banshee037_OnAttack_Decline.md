# JTL_037 Banshee — the On Attack damage is optional ("You may"). Declining (AnswerDecision:-) leaves
# the enemy unit untouched; Banshee still attacks the base for 4.

## GIVEN
P1LeaderBase: JTL_001/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_037:1:3
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:4
