# TWI_116 Clone — copy targets are non-leader, NON-VEHICLE units in play. When the only other unit in
# play is a Vehicle (SOR_099, a space Vehicle), there is NO eligible copy target: no copy prompt is
# offered, Clone enters as a plain 0/0, and is defeated (→ P1 discard as TWI_116). Proves Vehicles are
# excluded (an eligible Vehicle would have produced a copy prompt).
## GIVEN
CommonSetup: rrk/bbw/{myResources:11;handCardIds:TWI_116}
P1OnlyActions: true
WithP2SpaceArena: SOR_099:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDUNIT:0:CARDID:TWI_116
P1NODECISION
