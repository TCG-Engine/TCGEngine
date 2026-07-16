# FirstUpgradeCheaper
#// ASH_075 Pit Droid Team (Ground, 3/3) — The first upgrade you play on another friendly unit each phase
#// costs 1 resource less. With Pit Droid in play, P1 plays SOR_120 (cost 2, Command) onto SOR_095 (another
#// friendly unit) for 1, leaving 1 of 2 resources.
## GIVEN
CommonSetup: ggk/ggk/{myResources:2;handCardIds:SOR_120}
WithP1GroundArena: ASH_075:1:0
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1RESAVAILABLE:1
