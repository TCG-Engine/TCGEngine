# DefeatCreditDualExp
#// LAW_040 Taramyn Barcona (4/6) — When Played: you may defeat a Credit token (any player's). If you do,
#// give an Experience token to this unit and another friendly unit. Defeat P2's lone Credit token; Exp to
#// Taramyn and to SEC_080.

## GIVEN
CommonSetup: bgw/bgw/{myResources:5;theirResources:0}
WithP2Credits: 1
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_040

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LAW_040
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2CREDITCOUNT:0
