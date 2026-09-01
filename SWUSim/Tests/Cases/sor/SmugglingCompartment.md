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
#// COVERAGE (Phase C update — the three entries above are STALE and are superseded here):
#//           offer=NO LONGER DEFERRED: AttachPool_VehiclesOnly_BothSides passes, so the printed
#//           "Attach to a VEHICLE unit" restriction IS enforced — the pool is Vehicles from every arena
#//           and both sides, with a friendly non-Vehicle Trooper excluded ·
#//           control=NO LONGER N/A: EnemyHost_ReadiesTheHostControllersResource proves the granted
#//           On Attack rides the HOST and resolves for the host's CONTROLLER (P1 attaches, P2 attacks,
#//           P2 readies) — the second reading of the axis, who RESOLVES it ·
#//           reqboundary=NO LONGER N/A: SimulateRequestBoundary_GrantSurvivesToTheAttack — the attach
#//           and the attack are separate player actions, so the grant must be re-found by a fresh
#//           process after the boundary ·
#//           boundary pair also=ReadiesExactlyOne_NotAll (three exhausted → exactly one readied) vs
#//           AllResourcesAlreadyReady_AttackIsANoOp (nothing to ready → row untouched, no dangling
#//           decision), with NoUpgrade_NoReady as the grant-absent negative ·
#//           decline=N/A confirmed (no "you may" anywhere on the card; the attach target is part of
#//           playing the upgrade and the granted ready is mandatory and unprompted)

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

---

# NoUpgrade_NoReady
#// SOR_214 Smuggling Compartment — the NEGATIVE that makes OnAttack_ReadiesResource load-bearing. The
#// identical Vehicle attacks the identical board with the upgrade REMOVED: nothing readies, and the
#// host swings for its printed 2 rather than 3. Without this control an engine that readied a resource
#// on every attack would pass the section above.

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1SpaceArena: SOR_060:1:0
WithP1Resources: 1:SOR_095:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1RESAVAILABLE:0
P1RESCOUNT:1
P2BASEDMG:2

---

# HostGainsPlusOnePlusOne
#// SOR_214 Smuggling Compartment — the upgrade's own printed +1/+1 on top of the granted ability. The
#// Distant Patroller is a 2/1; wearing the Compartment it reads 3/2 with one upgrade attached. Read
#// statically so the stat change is pinned independently of any combat arithmetic.

## GIVEN
CommonSetup: yyk/yyk
WithActivePlayer: 1
WithP1SpaceArena: SOR_060:1:0
WithP1SpaceArenaUpgrade: 0:SOR_214

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:HP:2
P1SPACEARENAUNIT:0:UPGRADECOUNT:1

---

# ReadiesExactlyOne_NotAll
#// SOR_214 Smuggling Compartment — "Ready A resource", singular. The quantity discriminator: with THREE
#// exhausted resources the attack readies exactly one and leaves the other two exhausted. An impl that
#// readied the whole row (or looped over every exhausted slot) would show 3 here.

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1SpaceArena: SOR_060:1:0
WithP1SpaceArenaUpgrade: 0:SOR_214
WithP1Resources: 3:SOR_095:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1RESAVAILABLE:1
P1RESCOUNT:3

---

# AllResourcesAlreadyReady_AttackIsANoOp
#// SOR_214 Smuggling Compartment — the no-valid-target case. With nothing exhausted to ready, the
#// granted On Attack has no work: the attack resolves normally, the resource row is untouched (no
#// phantom slot appended, no count change), and no decision is left dangling for the player to answer.
#// The complement of OnAttack_ReadiesResource, which covers the fully-exhausted floor.

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1SpaceArena: SOR_060:1:0
WithP1SpaceArenaUpgrade: 0:SOR_214
WithP1Resources: 2:SOR_095:1

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1RESAVAILABLE:2
P1RESCOUNT:2
P1NODECISION
P2BASEDMG:3

---

# AttackingAUnit_AlsoReadies
#// SOR_214 Smuggling Compartment — the trigger is "On Attack", not "on attacking a base". Every existing
#// section attacks the base; here the host declares an attack on an enemy VEHICLE instead and the ready
#// must still happen. The 3/2 host trades into the 2/5 HWK-290: the Freighter takes 3 and lives, the
#// host takes 2 and dies — and the resource is readied all the same, because the trigger fires at
#// attack declaration, before any damage.

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1SpaceArena: SOR_060:1:0
WithP1SpaceArenaUpgrade: 0:SOR_214
WithP1Resources: 1:SOR_095:0
WithP2SpaceArena: SHD_060:1:0

## WHEN
- P1>AttackSpaceArena:0:theirSpaceArena-0

## EXPECT
P1RESAVAILABLE:1
P1SPACEARENACOUNT:0
P2SPACEARENAUNIT:0:DAMAGE:3
P2BASEDMG:0

---

# EnemyHost_ReadiesTheHostControllersResource
#// SOR_214 Smuggling Compartment — the CONTROL axis, and the reading the old ledger wrote off as N/A.
#// The printed restriction is "Attach to a VEHICLE unit" with no "friendly", so per CR 2.e P1 may hang
#// it on an ENEMY Vehicle — and the granted ability then belongs to the HOST, so it resolves for the
#// host's CONTROLLER. P1 attaches to P2's HWK-290 and P2 attacks with it: P2 readies one of its two
#// exhausted resources while P1's row (both exhausted after paying for the upgrade) stays exhausted.
#// An impl that credited the ability to the upgrade's caster would ready P1's resource instead.

## GIVEN
CommonSetup: yyk/yyk
WithActivePlayer: 1
WithP1Resources: 1:SOR_095:1,1:SOR_095:0
WithP2Resources: 2:SOR_095:0
WithP1Hand: SOR_214
WithP2SpaceArena: SHD_060:1:0

## WHEN
- P1>PlayHand:0
- P2>AttackSpaceArena:0:BASE

## EXPECT
P2SPACEARENAUNIT:0:UPGRADECOUNT:1
P1RESAVAILABLE:0
P1RESCOUNT:2
P2RESAVAILABLE:1
P2RESCOUNT:2
P1BASEDMG:3

---

# SimulateRequestBoundary_GrantSurvivesToTheAttack
#// SOR_214 Smuggling Compartment — the attach and the attack are separate player actions, so in
#// production the granted "On Attack: Ready a resource" is written by one request and must be found by a
#// fresh process in the next. Mirrors OnAttack_ReadiesResource with the upgrade PLAYED (rather than
#// pre-seated) and a boundary inserted between the attach and the attack.

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1Resources: 1:SOR_095:1,1:SOR_095:0
WithP1Hand: SOR_214
WithP1SpaceArena: SOR_060:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1RESAVAILABLE:1
P1RESCOUNT:2
P2BASEDMG:3
