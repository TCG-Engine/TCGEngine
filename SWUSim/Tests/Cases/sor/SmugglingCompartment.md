# OnAttack_ReadiesResource
#// SOR_214 Smuggling Compartment (Upgrade) — Attach to a Vehicle unit. Attached unit gains:
#// "On Attack: Ready a resource." A Vehicle (Distant Patroller, SOR_060) carries the upgrade
#// and attacks the enemy base; the upgrade's On Attack readies P1's one exhausted resource
#// (ready resources 0 → 1). Exercises the upgrade-granted On Attack path (OnAttackFromUpgrade).
#// COVERAGE: offer=DEFERRED — open candidate: the attach pool ignores the printed "Attach to a
#//           VEHICLE unit" restriction (non-Vehicle friendlies are offered as hosts); assert the
#//           Vehicles-only pool once the pool is restricted · decline=N/A (attach target is part
#//           of playing the upgrade; the On Attack ready is mandatory) · control=N/A (the granted
#//           On Attack rides the host; no control-change interaction printed) ·
#//           boundary=OnAttack_ReadiesResource covers the all-resources-exhausted floor (0→1) ·
#//           reqboundary=N/A (attach + ready both resolve inside their own ceremonies)

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1SpaceArena: SOR_060:1:0          # Vehicle host (ready) — attacker, idx 0
WithP1SpaceArenaUpgrade: 0:SOR_214     # Smuggling Compartment attached
WithP1Resources: 1:SOR_095:0           # one EXHAUSTED resource → to be readied

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1RESAVAILABLE:1
P1RESCOUNT:1

---

# AttachPool_VehiclesOnly_BothSides
#// Candidate #3 fix guard: printed "Attach to a VEHICLE unit" (no "friendly") — the pool is
#// Vehicles from all four arenas (CR 2.e enemy hosts legal) and EXCLUDES non-Vehicles. The
#// Trooper must not be offered; the enemy Vehicle must be. Offer left pending.

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SOR_214
WithP1SpaceArena: SOR_225:1:0
WithP1GroundArena: SOR_128:1:0
WithP2SpaceArena: SHD_060:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:mySpaceArena-0&theirSpaceArena-0
