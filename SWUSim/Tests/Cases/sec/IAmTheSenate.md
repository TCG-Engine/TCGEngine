# CreatesFiveSpies
#// SEC_092 I Am the Senate (Event, cost 6, Command/Villainy) — "Create 5 Spy tokens."

## GIVEN
CommonSetup: ggk/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: SEC_092

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:5
P1GROUNDARENAUNIT:0:CARDID:SEC_T01
P1NODECISION
