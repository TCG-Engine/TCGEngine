# SHD_090 Maul — redirected damage is COMBAT damage, so the chosen unit's own Shield absorbs it. LAW_124
# has a Shield; the redirected 2 is absorbed (LAW_124 undamaged, shield gone), Maul takes 0.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: [SHD_090:1:0 LAW_124:1:0]
WithP1GroundArenaUpgrade: 1:SOR_T02
WithP2GroundArena: SOR_181:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:CARDID:LAW_124
P1GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0
