# Twin Suns Phase 3: in an N-player game, a "choose an enemy ground unit" search (theirGroundArena)
# spans ALL opponents. P1's search finds P2's 1 unit + P3's 2 units = 3 (the picked target's owner is
# implied by its seat-specific mzID). In 2-player this is unchanged (single opponent).

## GIVEN
CommonSetup: grw/ggk
WithSeatOrder: 123
WithP2GroundArena: SOR_229:1:0
WithP3GroundArena: SOR_229:1:0
WithP3GroundArena: SOR_229:1:0

## WHEN
- P1>UndoCycle

## EXPECT
SEATCOUNT:3
ZONESEARCH:1:theirGroundArena:3
ZONESEARCH:2:theirGroundArena:2
