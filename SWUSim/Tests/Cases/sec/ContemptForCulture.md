# Deal2NonVehicle_CreateSpy
#// SEC_246 Contempt for Culture (Event, cost 2, Villainy) — "Deal 2 damage to a non-Vehicle unit.
#//   Create a Spy token." Enemy SOR_046 (non-Vehicle) takes 2; a Spy is created.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SEC_246
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_T01
P1NODECISION
