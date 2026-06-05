# JTL_151 Red Five — On Attack: You may deal 2 damage to a DAMAGED unit. Red Five attacks P2's base; on
# attack it deals 2 to the damaged SOR_046 (2 → 4 damage). Undamaged units are not offered.

## GIVEN
P1LeaderBase: JTL_001/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_151:1:0
WithP2GroundArena: SOR_046:1:2

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P2BASEDMG:3
