# SHD_057 Rickety Quadjumper — when the revealed top card IS a unit (SOR_095), no Experience is given. The
# deck is unchanged (card left on top).

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_057:1:0
WithP1GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_251]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_095
