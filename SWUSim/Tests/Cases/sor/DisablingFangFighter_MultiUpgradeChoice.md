# SOR_162 Disabling Fang Fighter — MultiUpgradeChoice
# Only one upgrade-bearing unit (auto-selected). Two Shield tokens on it
# so the player picks which to defeat via the staged TempZone pick. Picking
# myTempZone-1 leaves the first shield intact. Defeated token is set aside — not discarded.

## GIVEN
CommonSetup: rbk/grw/{myResources:3;handCardIds:SOR_162}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myTempZone-1

## EXPECT
P1SPACEARENACOUNT:1
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P2DISCARDCOUNT:0
