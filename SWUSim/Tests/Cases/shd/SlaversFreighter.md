# WhenPlayed_ReadyLowPowerUnit
#// SHD_189 Slaver's Freighter (5-cost space) — "When Played: You may ready another unit with power equal to
#// or less than the number of upgrades on enemy units." The enemy SEC_080 carries 2 upgrades → threshold 2,
#// so the exhausted friendly SOR_063 (power 2) can be readied.

## GIVEN
CommonSetup: yyk/yyk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_189
WithP1GroundArena: SOR_063:0:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_063
P1GROUNDARENAUNIT:0:READY
