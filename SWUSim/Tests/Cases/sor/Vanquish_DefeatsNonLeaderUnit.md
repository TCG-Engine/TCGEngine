# SOR_078 Vanquish (Event, cost 5) — "Defeat a non-leader unit." P2's only unit is
# a non-leader (Battlefield Marine, SOR_095), so it is the sole target and is
# auto-defeated.

## GIVEN
CommonSetup: bbk/bbk/{myResources:5;handCardIds:SOR_078}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
