# StealUpgradeAndReattach
#// SHD_077 Take Control (3-cost event, Vigilance) — "Take control of an upgrade that costs 3 or less and
#// attach it to an eligible unit of your choice." P1 takes SOR_120 (cost 3) off the enemy SEC_080 and
#// re-attaches it to the friendly SOR_095.

## GIVEN
CommonSetup: bbw/bbw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_077
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
