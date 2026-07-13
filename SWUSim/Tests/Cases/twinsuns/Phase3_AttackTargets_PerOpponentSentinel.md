# Twin Suns Phase 3: attack targeting UNIONS all opponents, and Sentinel is PER-OPPONENT (CR §11.4.4 —
# a Sentinel on opponent A only forces attacks against A's units, it does NOT restrict attacks against
# opponent B). P1's ground unit sees: P2's lone Sentinel (SOR_229) — P2's base is dropped because of it —
# PLUS P3's non-Sentinel unit AND P3's base (unaffected by P2's Sentinel) = 3 targets. A broken (global)
# Sentinel model would return only 1 (the Sentinel); a lone-opponent model would return only P2's 1.

## GIVEN
CommonSetup: grw/ggk
WithSeatOrder: 123
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_229:1:0
WithP3GroundArena: LAW_124:1:0
WithP3Base: SOR_019

## WHEN
- P1>UndoCycle

## EXPECT
SEATCOUNT:3
ATTACKTARGETS:1:G:0:3
