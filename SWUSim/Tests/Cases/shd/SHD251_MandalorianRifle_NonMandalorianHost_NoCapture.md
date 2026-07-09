# SHD_251 — the capture is gated on the host being "The Mandalorian". Attached to a non-Mandalorian
# host (SOR_046) with an exhausted enemy present, no capture happens.

## GIVEN
CommonSetup: bbw/bbw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SHD_251
WithP2GroundArena: SHD_095:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
