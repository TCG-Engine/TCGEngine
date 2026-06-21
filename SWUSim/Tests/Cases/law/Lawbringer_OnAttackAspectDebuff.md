# LAW_101 Lawbringer (7/7, space) — When Played/On Attack: choose an aspect; give each enemy unit with
# that aspect -2/-2 for this phase. Attacks the base; choose Heroism -> SOR_046 (Vigilance,Heroism) 3/7
# -> 1/5.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_101:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Heroism

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:5
