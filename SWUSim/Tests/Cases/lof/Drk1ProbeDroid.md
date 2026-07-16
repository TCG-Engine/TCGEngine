# DefeatNonUniqueUpgrade
#// LOF_155 DRK-1 Probe Droid — When Played: may defeat a non-unique upgrade. P1 defeats the Resilient
#// (non-unique) upgrade on the enemy 3/7.

## GIVEN
CommonSetup: rrk/ggw/{myResources:2;handCardIds:LOF_155}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
