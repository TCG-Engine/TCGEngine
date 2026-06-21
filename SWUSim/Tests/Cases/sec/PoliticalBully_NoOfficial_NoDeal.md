# SEC_241 Political Bully — no other Official controlled → no damage offered.

## GIVEN
CommonSetup: rrk/grw/{myResources:3}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_241

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
