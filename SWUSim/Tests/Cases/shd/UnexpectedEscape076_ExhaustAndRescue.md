# SHD_076 Unexpected Escape (1-cost event, Vigilance) — "Exhaust a unit. You may rescue a captured card
# guarded by that unit." First the Discerning Veteran (SHD_120) captures SOR_046; then Unexpected Escape
# exhausts the Veteran and rescues SOR_046 back to P2's arena.

## GIVEN
CommonSetup: bgk/bgk/{myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_120
WithP1Hand: SHD_076
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_120
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
