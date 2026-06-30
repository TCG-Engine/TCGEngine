# LOF_248 Jocasta Nu — When Played: may move a friendly upgrade to a different eligible unit. P1 moves
# Resilient from SOR_046 to SOR_095.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3;handCardIds:LOF_248}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_069
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
