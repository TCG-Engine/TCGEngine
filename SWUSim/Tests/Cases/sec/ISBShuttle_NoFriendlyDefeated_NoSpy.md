# SEC_083 ISB Shuttle — no friendly unit defeated this phase → no Spy token.

## GIVEN
CommonSetup: ggk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SEC_083

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SEC_083
P1GROUNDARENACOUNT:0
P1NODECISION
