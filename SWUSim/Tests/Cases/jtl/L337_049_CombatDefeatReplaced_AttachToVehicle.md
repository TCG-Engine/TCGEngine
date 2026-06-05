# JTL_049 L3-37 — the "if would be defeated" replacement also covers COMBAT defeats. L3-37 (3/3) attacks
# SOR_046 (3/7): she takes 3 lethal combat damage, but instead of being defeated her controller (P1)
# attaches her as a pilot upgrade onto the friendly Vehicle SEC_214. SOR_046 survives (7 HP).

## GIVEN
P1LeaderBase: SOR_002/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_049:1:0
WithP1GroundArena: SEC_214:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_214
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:JTL_049
P1DISCARDCOUNT:0
