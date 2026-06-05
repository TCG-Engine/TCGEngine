# SOR_170 Power Failure — defeats all upgrades on chosen unit
# P2 unit has two non-token upgrades; Select All (both staged picks) defeats both,
# they go to P2's discard.

## GIVEN
CommonSetup: grw/grw/{myResources:2;handCardIds:SOR_170}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:LOF_215
WithP2GroundArenaUpgrade: 0:SOR_215

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0&myTempZone-1

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1DISCARDCOUNT:1
P2DISCARDCOUNT:2
P1RESAVAILABLE:0
