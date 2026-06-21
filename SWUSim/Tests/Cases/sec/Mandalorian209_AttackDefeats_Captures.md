# SEC_209 The Mandalorian (Ground, 6/8, Cunning/Heroism) — Ambush + when this unit attacks and defeats
#   a unit, may capture an enemy non-leader. Attacks SOR_095 (idx1) and defeats it, then captures SOR_046 (idx0).

## GIVEN
CommonSetup: yyw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_209:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:1
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SEC_209
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION
