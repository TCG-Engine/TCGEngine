# WhenPlayed_ExpAnotherCheapUnit
#// SHD_082 Outland TIE Vanguard (2-cost) — "When Played: You may give an Experience token to another unit
#// that costs 3 or less." P1 gives the token to the friendly SEC_080 (cost 3).

## GIVEN
CommonSetup: ggk/ggk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_082
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
