# SEC_082 Chancellor Palpatine — no leader unit controlled → no Spy tokens created.
# CommonSetup leaves the leader undeployed, so SWUControlsLeaderUnit is false.

## GIVEN
CommonSetup: ggk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SEC_082

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_082
P1NODECISION
