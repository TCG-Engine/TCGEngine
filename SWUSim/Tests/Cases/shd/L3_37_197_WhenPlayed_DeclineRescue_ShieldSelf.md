# SHD_197 L3-37 — declining the rescue ("If you don't…"): the captive stays put and L3-37 gets a
# Shield token instead.

## GIVEN
CommonSetup: gyw/grw/{myResources:5;handCardIds:SHD_131,SHD_197}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:CARDID:SHD_197
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
P2GROUNDARENACOUNT:0
