# LAW_178 Persecutor (9/7, space) — When Played/On Attack: choose an arena. You may deal 3 damage to
# each unit in that arena. Attacks the base; choose Ground -> each ground unit takes 3 (SOR_046 3/7
# survives at DAMAGE:3; SOR_095 3/3 dies).

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_178:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Ground

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
