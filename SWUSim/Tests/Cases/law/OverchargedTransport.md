# WhenPlayedDefeatSpaceUpgrade
#// LAW_195 Overcharged Transport (4/3, space) — When Played/When Defeated: you may defeat an upgrade
#// attached to a space unit. Enemy SOR_237 bears SOR_120; play LAW_195 -> defeat it.

## GIVEN
CommonSetup: rrw/bgw/{myResources:4}
P1OnlyActions: true
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArenaUpgrade: 0:SOR_120
WithP1Hand: LAW_195

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:UPGRADECOUNT:0
