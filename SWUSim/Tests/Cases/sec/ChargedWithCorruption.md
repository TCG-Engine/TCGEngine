# Disclose_FriendlyCaptures
#// SEC_127 Charged with Corruption (Event, Command, cost 3) — disclose CommandCommand → a friendly unit
#//   captures an enemy non-leader unit. SOR_095 captures SOR_046.

## GIVEN
CommonSetup: ggk/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_127
WithP1Hand: SEC_080
WithP1Hand: SEC_080

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0&myHand-1

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION
