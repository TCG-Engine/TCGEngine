# JTL_160 Supporting Eta-2 — On Attack: You may give a ground unit +2/+0 this phase. JTL_160 attacks
# P2's base and buffs the friendly ground SOR_095 (3 → 5 power).

## GIVEN
P1LeaderBase: JTL_001/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_160:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:5
P2BASEDMG:2
