# Raid_ScalesWithExhaustedResources
#// HMW_117 Chewbacca, Resourceful Wookiee (Ground, 0/5, cost 3, Command/Heroism, Wookiee, unique):
#//   "This unit gains Raid 1 for each exhausted resource you control.
#//    While each resource you control is exhausted, this unit gains Overwhelm."
#// His printed power is ZERO, so every point of damage he deals comes from the Raid clause — which
#// makes the attack damage a direct readout of the Raid value. 2 of 5 resources exhausted -> Raid 2.
#// COVERAGE: offer=N/A (no targeting on either clause) · decline=N/A (nothing optional) ·
#//           boundary=Raid_NoExhaustedResources_DealsZero (0 vs N) + Overwhelm_OneResourceReady_NoOverwhelm
#//           (all-exhausted vs one-ready) · control=Raid_ReadsTheCONTROLLERsResources ·
#//           reqboundary=N/A (both clauses are recomputed on every read; no state is written across a
#//           decision, and there is no decision to cross)

## GIVEN
CommonSetup: ggw/rrk/{}
P1OnlyActions: true
WithP1Resources: 3:SOR_095:1,2:SOR_095:0
WithP1GroundArena: HMW_117:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2
P1GROUNDARENAUNIT:0:POWER:0

---

# Raid_NoExhaustedResources_DealsZero
#// HMW_117 — the zero case, and the boundary partner for the section above. With every resource READY
#// the Raid clause contributes nothing, and because his printed power is 0 the attack deals literally
#// no damage. This is what separates "Raid 1 for each EXHAUSTED resource" from "Raid 1 for each
#// resource you control" — a count-based misreading would deal 5 here.

## GIVEN
CommonSetup: ggw/rrk/{}
P1OnlyActions: true
WithP1Resources: 5:SOR_095:1
WithP1GroundArena: HMW_117:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:0
P1GROUNDARENAUNIT:0:POWER:0

---

# Raid_CreditTokensAreNotResources
#// HMW_117 — a Credit token sits in the resource zone but is NOT a resource (CR 3.13), so an exhausted
#// Credit must not add to Raid. 2 real exhausted resources + 2 exhausted Credits must still be Raid 2.
#// ⚠ This is the exact shape that made Seasoned Shoretrooper wrong for months: counting the raw
#// resource zone instead of SWUResourceCount silently includes Credits.

## GIVEN
CommonSetup: ggw/rrk/{}
P1OnlyActions: true
WithP1Resources: 3:SOR_095:1,2:SOR_095:0
WithP1Credits: 2
WithP1GroundArena: HMW_117:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2

---

# Raid_ReadsTheCONTROLLERsResources
#// HMW_117 — "you control" means the unit's CONTROLLER, not its owner. Chewbacca is owned by P2 but
#// controlled by P1, so the Raid value must count P1's exhausted resources (2), not P2's (5).
#// Both seats are seeded so a wrong read produces a DIFFERENT number rather than an absent one.

## GIVEN
CommonSetup: ggw/rrk/{}
P1OnlyActions: true
WithP1Resources: 3:SOR_095:1,2:SOR_095:0
WithP2Resources: 5:SOR_095:0
WithP1GroundArenaControlled: HMW_117:2

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2

---

# Overwhelm_AllResourcesExhausted_Granted
#// HMW_117 — second clause. With every resource exhausted he gains Overwhelm. Asserted as a keyword
#// so the grant itself is pinned independently of any combat arithmetic.

## GIVEN
CommonSetup: ggw/rrk/{}
P1OnlyActions: true
WithP1Resources: 5:SOR_095:0
WithP1GroundArena: HMW_117:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm

---

# Overwhelm_OneResourceReady_NoOverwhelm
#// HMW_117 — the boundary partner: "EACH resource you control is exhausted" fails the moment ONE is
#// ready. 4 exhausted + 1 ready must NOT grant Overwhelm. A "some resource is exhausted" misreading
#// passes the positive above and fails only here.

## GIVEN
CommonSetup: ggw/rrk/{}
P1OnlyActions: true
WithP1Resources: 1:SOR_095:1,4:SOR_095:0
WithP1GroundArena: HMW_117:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Overwhelm

---

# Overwhelm_ReadyCreditTokenDoesNotBlockIt
#// HMW_117 — the mirror of the Credit case on the OTHER clause. A Credit token is not a resource, so a
#// READY Credit must not stop "each resource you control is exhausted" from being true. Every real
#// resource is exhausted, so Overwhelm stands.
#// Sharp because the two clauses fail in opposite directions under the same bug: counting the raw zone
#// inflates Raid AND suppresses Overwhelm.

## GIVEN
CommonSetup: ggw/rrk/{}
P1OnlyActions: true
WithP1Resources: 5:SOR_095:0
WithP1Credits: 1
WithP1GroundArena: HMW_117:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm

---

# BothClauses_RaidDamageOverwhelmsToBase
#// HMW_117 — the two clauses working together, which is the card's whole point: with all 5 resources
#// exhausted he is a 5-power attacker (0 printed + Raid 5) WITH Overwhelm. Attacking a 1-HP unit
#// defeats it and spills the remaining 4 to the base.
#// The counter-damage (Death Star Stormtrooper's 3 power) lands on his 5 HP and he survives at 3.

## GIVEN
CommonSetup: ggw/rrk/{}
P1OnlyActions: true
WithP1Resources: 5:SOR_095:0
WithP1GroundArena: HMW_117:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:4
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# NoOverwhelm_ExcessIsLostNotSpilled
#// HMW_117 — the same attack with ONE resource ready: Raid drops to 4 and Overwhelm is gone, so the
#// 1-HP defender still dies but the 3 excess damage is LOST rather than spilling to the base.
#// This is the section that proves the Overwhelm clause is doing work — the defeat alone looks
#// identical with or without it.

## GIVEN
CommonSetup: ggw/rrk/{}
P1OnlyActions: true
WithP1Resources: 1:SOR_095:1,4:SOR_095:0
WithP1GroundArena: HMW_117:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:0
P1GROUNDARENAUNIT:0:DAMAGE:3
