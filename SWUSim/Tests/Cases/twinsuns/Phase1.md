# FourPlayer_StorageRoundTrip
## GIVEN
#// Twin Suns Phase 1: 4-player seat/zone storage. Distinct per-seat ground counts (1/2/3/4) must be
#// stored independently in the p1..p4 zone globals AND survive a SaveVersion->LoadVersion round-trip
#// (UndoCycle), proving all four seats serialize through the extended Versions module.
CommonSetup: grw/ggk
WithSeatOrder: 1234
WithP1GroundArena: SOR_229:1:0
WithP2GroundArena: SOR_229:1:0
WithP2GroundArena: SOR_229:1:0
WithP3GroundArena: SOR_229:1:0
WithP3GroundArena: SOR_229:1:0
WithP3GroundArena: SOR_229:1:0
WithP4GroundArena: SOR_229:1:0
WithP4GroundArena: SOR_229:1:0
WithP4GroundArena: SOR_229:1:0
WithP4GroundArena: SOR_229:1:0

## WHEN
- P1>UndoCycle

## EXPECT
SEATCOUNT:4
SEATLIVE:1:true
SEATLIVE:4:true
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:2
P3GROUNDARENACOUNT:3
P4GROUNDARENACOUNT:4

---

# ThreePlayer_StorageRoundTrip
## GIVEN
#// Twin Suns Phase 1: 3-player seat/zone storage. Distinct per-seat ground counts (1/2/3) must be
#// stored independently AND survive a SaveVersion->LoadVersion round-trip (UndoCycle), proving the
#// p3 zone globals + the extended Versions module serialize correctly.
CommonSetup: grw/ggk
WithSeatOrder: 123
WithP1GroundArena: SOR_229:1:0
WithP2GroundArena: SOR_229:1:0
WithP2GroundArena: SOR_229:1:0
WithP3GroundArena: SOR_229:1:0
WithP3GroundArena: SOR_229:1:0
WithP3GroundArena: SOR_229:1:0

## WHEN
- P1>UndoCycle

## EXPECT
SEATCOUNT:3
SEATLIVE:1:true
SEATLIVE:2:true
SEATLIVE:3:true
SEATLIVE:4:false
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:2
P3GROUNDARENACOUNT:3
