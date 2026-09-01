# HostAttacks_NoReduction
#// SOR_071 Electrostaff (Upgrade, cost 2, [Vigilance], Item/Weapon, +2/+2) — "Attach to a non-VEHICLE
#// unit. While attached unit is defending, the attacker gets -1/-0."
#// COVERAGE: offer=Offer_AttachPool_ExcludesVehicles_AndSpansBothSides (attach menu asserted on a
#//           PENDING decision — a Vehicle excluded, an enemy non-Vehicle included per CR 2.e) ·
#//           boundary pair=Boundary_TwoPowerAttacker_ReducedToOne (2 → 1) vs
#//           Boundary_OnePowerAttacker_ReducedToZero_DealsNothing (1 → 0, floors at zero) ·
#//           decline=N/A — neither clause is optional: the attach is a cost-free mandatory placement
#//           of a played upgrade and the -1/-0 is a continuous effect with no "you may" ·
#//           control change=EnemyAttackerIsReducedToo_TheEffectFollowsTheHostNotASeat + the pair
#//           HostDefends_AttackerReduced / HostAttacks_NoReduction — the modifier is resolved from the
#//           DEFENDER of the current attack, never from a stored seat, so both directions and both
#//           roles are exercised; there is no "your"-zone clause for an owner≠controller board to
#//           mis-resolve · request boundary=N/A — nothing pends: the -1/-0 is computed inside
#//           SWUCombatDamage during a single attack request and no state survives to a later request
#//           (the only pending state on this card is the attach choice, held open and asserted by the
#//           offer section)
#// The -1/-0 applies ONLY while the host is DEFENDING. When the Electrostaff host
#// (SOR_095 + upgrade → 5/5) ATTACKS, it deals its full 5 to the defender (no self-reduction).

## GIVEN
CommonSetup: rrw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_071
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# HostDefends_AttackerReduced
#// SOR_071 Electrostaff (Vigilance upgrade, cost 2, +2/+2, non-Vehicle) — "While attached unit is
#// defending, the attacker gets -1/-0." P2's SOR_046 (3/7) carries Electrostaff (→ 5/9). P1's SOR_095
#// (3 power) attacks it: the attacker's power is reduced to 2, so the host takes DAMAGE:2 (not 3). The
#// host's 5-power counter kills SOR_095.

## GIVEN
CommonSetup: rrw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_071

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENACOUNT:0

---

# Offer_AttachPool_ExcludesVehicles_AndSpansBothSides
#// THE OFFER CELL — the attach menu, asserted while it is still PENDING (an answered choice leaves no
#// offer to inspect). SOR_071 prints "Attach to a non-VEHICLE unit." with NO controller qualifier, so
#// per CR 2.e either side's non-Vehicle units are legal hosts. Board:
#//   myGroundArena-0  SOR_095 Battlefield Marine  (Rebel,Trooper)      → legal
#//   myGroundArena-1  SOR_148 Guerilla Attack Pod (Rebel,VEHICLE,Walker) → EXCLUDED, the control that
#//                    proves the non-Vehicle gate is load-bearing rather than "everything is legal"
#//   theirGroundArena-0 SOR_046 Consular Security Force (Rebel,Trooper) → legal, and the control that
#//                    proves the pool is NOT narrowed to friendly units
#// Two legal hosts are required or the pick auto-resolves and there is no menu at all.

## GIVEN
CommonSetup: bbw/grw/{myResources:3;myhandCardIds:SOR_071}
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 SOR_148:1:0]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# Boundary_TwoPowerAttacker_ReducedToOne
#// SOR_071 "the attacker gets -1/-0" — the N side of the boundary pair. A 2-power attacker
#// (TWI_T02 Clone Trooper token, 2/2) hits the Electrostaff host and lands 2−1 = 1 damage. The host
#// (SOR_046, 3/7 + the staff's +2/+2 = 5/9) counters for 5 and kills the 2/2 token.
#// Paired with Boundary_OnePowerAttacker_ReducedToZero_DealsNothing below: 1 damage vs 0 is the only
#// thing that distinguishes "reduced by exactly 1" from "reduced to nothing".

## GIVEN
CommonSetup: rrw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: TWI_T02:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_071

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:POWER:5
P2GROUNDARENAUNIT:0:HP:9
P1GROUNDARENACOUNT:0

---

# Boundary_OnePowerAttacker_ReducedToZero_DealsNothing
#// The N−1 side: a 1-power attacker (TWI_057 Warrior Drone, 1/4, vanilla) is reduced to 0 and deals
#// NO damage at all to the Electrostaff host — power floors at zero, it never goes negative and never
#// wraps into healing. The host still counters for its full 5 and kills the drone.
#// Read against Boundary_TwoPowerAttacker_ReducedToOne this pins the reduction at exactly −1.

## GIVEN
CommonSetup: rrw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: TWI_057:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_071

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENACOUNT:0

---

# EnemyAttackerIsReducedToo_TheEffectFollowsTheHostNotASeat
#// The mirror of HostDefends_AttackerReduced: there P1 attacked a P2-hosted staff, here P2 attacks a
#// P1-hosted staff. "While attached unit is DEFENDING, the attacker gets -1/-0" names no seat — it
#// reads the defender of the current attack — so it must fire in both directions. P2's 3-power
#// SOR_095 attacks P1's staffed SOR_046 (5/9) and lands 3−1 = 2; the host counters for 5 and kills it.
#// If the reduction were wired to one seat's units this section reds while HostDefends_AttackerReduced
#// stays green.

## GIVEN
CommonSetup: rrw/rrk/{}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_071
WithP2GroundArena: SOR_095:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENACOUNT:0
