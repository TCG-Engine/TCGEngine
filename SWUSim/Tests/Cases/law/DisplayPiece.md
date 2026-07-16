# DefeatThenResource
#// LAW_103 Display Piece (Vigilance,Villainy event, cost 4) — "Defeat an enemy non-leader unit. Its
#// controller resources it from its owner's discard pile." Defeat P2's SEC_080 (single target ->
#// auto-resolves); P2 resources it (exhausted). P2 started with 0 resources.

## GIVEN
CommonSetup: brk/rrk/{myResources:4;theirResources:0}
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LAW_103

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2RESCOUNT:1
P2RESAVAILABLE:0
P2DISCARDCOUNT:0
P1DISCARDCOUNT:1
