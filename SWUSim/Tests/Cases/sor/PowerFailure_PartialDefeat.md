# SOR_170 Power Failure — defeat a subset of multiple upgrades
# Host has 3 non-token upgrades (LOF_215 @0, SOR_120 @1, SOR_215 @2). The player picks
# myTempZone-0 and myTempZone-2 → defeats LOF_215 + SOR_215, leaving SOR_120 (reindexed to 0).
# Verifies the positional myTempZone-N → matchIdx[N] map and descending-defeat with a partial pick.

## GIVEN
CommonSetup: grw/grw/{myResources:2;handCardIds:SOR_170}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:LOF_215
WithP2GroundArenaUpgrade: 0:SOR_120
WithP2GroundArenaUpgrade: 0:SOR_215

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0&myTempZone-2

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_120
P2DISCARDCOUNT:2
P1DISCARDCOUNT:1
