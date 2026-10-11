# Aura_OtherRepublicUnits_BothArenas_GetPlus0Plus1
#// IC27_034 Obi-Wan's Interceptor — Nothing Too Fancy (2/3 Space, Vigilance/Heroism, Jedi·Republic·Vehicle·Fighter)
#// "Other friendly Republic units get +0/+1."
#// One continuous clause, read live in ObjectCurrentHP through _SWUIc27034Bonus (card file).
#//
#// COVERAGE: offer=N/A (a continuous aura — nothing is chosen, so there is no pool to assert) ·
#//           decline=N/A (no "you may"; the aura is not optional) ·
#//           boundary=Survival_AuraKeepsCloneAliveOnTwoDamage paired with Survival_WithoutInterceptorCloneDies
#//           (2 damage on a printed-2-HP unit: lives at 3 HP, dies at 2) ·
#//           control=Control_StolenRepublicUnit_IsFriendly (recipient read by controller, not owner) +
#//           Control_StolenInterceptorStopsBuffingItsOwner (a stolen Interceptor buffs its NEW controller's
#//           units and stops buffing its owner's) ·
#//           reqboundary=N/A (nothing is stored — the bonus is recomputed from the live board on every HP read;
#//           EntersLater_ section still crosses one SimulateRequestBoundary as a cheap guard) ·
#//           modes=2P,TeamSuns (text says "friendly") · TwinSuns=TwinSuns_FarSeatRepublicUnit_IsNotFriendly
#//           (no player reference, but a 4-seat free-for-all pins that "friendly" did not widen to every seat)
#//
#// Self-exclusion: the Interceptor is itself Republic, so "other" is load-bearing — it stays 2/3.
#// +0/+1 means POWER is untouched. Covers BOTH arenas (the source is in space, a recipient on the ground).

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
WithP1SpaceArena: IC27_034:1:0    # idx 0 — Obi-Wan's Interceptor 2/3
WithP1SpaceArena: TWI_209:1:0     # idx 1 — Hotshot V-Wing 2/3 (Republic, vanilla)
WithP1GroundArena: TWI_241:1:0    # idx 0 — Phase I Clone Trooper 3/2 (Republic, vanilla)

## WHEN

## EXPECT
P1SPACEARENAUNIT:0:HP:3
P1SPACEARENAUNIT:0:POWER:2
P1SPACEARENAUNIT:1:HP:4
P1SPACEARENAUNIT:1:POWER:2
P1GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:0:POWER:3

---

# NonRepublic_RebelAndNewRepublic_GetNothing
#// Trait-scope negatives. SOR_095 is a Rebel. SEC_044 Populist Champion is "New Republic" — a different
#// trait whose NAME contains "Republic", so a substring match would wrongly buff it (3/5 -> 3/6).

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
WithP1SpaceArena: IC27_034:1:0
WithP1GroundArena: SOR_095:1:0    # idx 0 — Battlefield Marine 3/3 (Rebel)
WithP1GroundArena: SEC_044:1:0    # idx 1 — Populist Champion 3/5 (New Republic)
WithP1GroundArena: TWI_241:1:0    # idx 2 — Phase I Clone Trooper 3/2 (positive control)

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:1:HP:5
P1GROUNDARENAUNIT:2:HP:3

---

# EnemyRepublicUnits_GetNothing
#// "friendly" — the opponent's Republic units, in both arenas, are untouched.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
WithP1SpaceArena: IC27_034:1:0
WithP2GroundArena: TWI_241:1:0    # enemy Phase I Clone Trooper 3/2
WithP2SpaceArena: TWI_209:1:0     # enemy Hotshot V-Wing 2/3

## WHEN

## EXPECT
P2GROUNDARENAUNIT:0:HP:2
P2SPACEARENAUNIT:0:HP:3

---

# ValueClasses_RepublicTokenAndRepublicLeaderUnit_GetIt
#// "units" includes a TOKEN unit (TWI_T02 Clone Trooper 2/2) and a deployed LEADER unit (TWI_004 Yoda,
#// Force·Jedi·Republic, 4/9 deployed). Both are Republic, both are friendly, neither is the source.

## GIVEN
CommonSetup: bbw/rrk/{myLeader:TWI_004:1:1}
SkipPreGame: true
WithP1SpaceArena: IC27_034:1:0
WithP1GroundArena: TWI_T02:1:0    # idx 0 — Clone Trooper token 2/2; deployed Yoda lands after it (idx 1)

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_T02
P1GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:1:CARDID:TWI_004
P1GROUNDARENAUNIT:1:HP:10

---

# Survival_AuraKeepsCloneAliveOnTwoDamage
#// The +1 HP is REAL in combat: a Phase I Clone Trooper (printed 2 HP) already on 1 damage attacks a
#// Battle Droid (1/1) and takes 1 more. With the Interceptor it sits at 2 damage on 3 HP and lives.
#// Boundary partner: Survival_WithoutInterceptorCloneDies (same board, no Interceptor).

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: IC27_034:1:0
WithP1GroundArena: TWI_241:1:1    # Clone Trooper, ready, 1 damage
WithP2GroundArena: TWI_T01:1:0    # Battle Droid 1/1

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# Survival_WithoutInterceptorCloneDies
#// Boundary partner of the section above: identical board minus the Interceptor — 2 damage on a 2-HP
#// Clone Trooper is lethal. Pins that the survival above came from the aura's +1.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TWI_241:1:1
WithP2GroundArena: TWI_T01:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:0

---

# Ends_InterceptorDefeatedByEffect_DamagedRepublicAllyDies
#// The aura RECOMPUTES — it ends the moment the Interceptor leaves play. Vanquish (SOR_078) defeats the
#// Interceptor; the Clone Trooper on 2 damage drops to 2 HP and is defeated by the state-based sweep
#// (_SWUSweepAfterDefeat). The undamaged Clone Trooper survives and reads printed 2 HP again.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_078
WithP1SpaceArena: IC27_034:1:0
WithP1GroundArena: TWI_241:1:2    # idx 0 — 2 damage (alive only because of the +1)
WithP1GroundArena: TWI_241:1:0    # idx 1 — undamaged

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:HP:2

---

# Ends_InterceptorDiesInCombat_DamagedRepublicAllyDies
#// Same END cell through the COMBAT defeat path (a separate code path from the effect defeat): the
#// Interceptor (2/3) attacks a Munificent Frigate (4/7) and dies to the counter-damage. The Clone
#// Trooper on 2 damage loses its +1 and is defeated by combat's post-step-3 sweep.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: IC27_034:1:0
WithP1GroundArena: TWI_241:1:2
WithP2SpaceArena: JTL_069:1:0     # Munificent Frigate 4/7

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENAUNIT:0:DAMAGE:2
P1GROUNDARENACOUNT:0

---

# Control_StolenRepublicUnit_IsFriendly
#// "friendly" = the recipient's CONTROLLER, not its owner. P1 controls a P2-owned Clone Trooper (sorts
#// after P1's plain units) — it IS friendly to P1's Interceptor: 3 HP. Only P1 has an Interceptor, so an
#// owner-based read (which would look at P2's board) finds none and leaves it at 2 — the discriminator.
#// P2's own Clone is enemy: printed 2 HP.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
WithP1SpaceArena: IC27_034:1:0               # P1's own Interceptor
WithP1GroundArena: TWI_241:1:0               # P1 idx 0 — own Clone
WithP1GroundArenaControlled: TWI_241:2       # P1 idx 1 — P2-owned Clone, P1-controlled
WithP2GroundArena: TWI_241:1:0               # P2 idx 0 — P2's own Clone

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:1:CARDID:TWI_241
P1GROUNDARENAUNIT:1:HP:3
P2GROUNDARENAUNIT:0:HP:2

---

# Control_StolenInterceptorStopsBuffingItsOwner
#// The other half of the control cell, isolated: P1 OWNS the Interceptor but P2 controls it, and P1
#// has no Interceptor of its own — P1's Clone Trooper reads its printed 2 HP.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
WithP2SpaceArenaControlled: IC27_034:1
WithP1GroundArena: TWI_241:1:0
WithP2GroundArena: TWI_241:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:HP:2
P2GROUNDARENAUNIT:0:HP:3

---

# EntersLater_PlayedRepublicUnitGetsIt
#// A continuous aura — not a snapshot at the Interceptor's entry. A Clone Trooper played AFTER it gets
#// +0/+1 too. Crosses one request boundary between the play and the read (nothing is stored, so this is
#// a cheap guard that the bonus is recomputed rather than stamped).

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TWI_241
WithP1SpaceArena: IC27_034:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_241
P1GROUNDARENAUNIT:0:HP:3

---

# Blanked_InterceptorWithNoAbilities_GrantsNothing
#// The aura is the Interceptor's ability: under SOR_138 Force Lightning's "loses all abilities for this
#// phase" it grants nothing.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
WithP1SpaceArena: IC27_034:1:0:SOR_138
WithP1GroundArena: TWI_241:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:HP:2

---

# TeamSuns_TeammatesRepublicUnit_GetsIt
#// Team Suns: "friendly" is the TEAM (user ruling 2026-08-25, IBH_095). P1's Interceptor buffs teammate
#// P3's Clone Trooper; the enemies P2 and P4 get nothing.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1SpaceArena: IC27_034:1:0
WithP2GroundArena: TWI_241:1:0
WithP3GroundArena: TWI_241:1:0
WithP4GroundArena: TWI_241:1:0

## WHEN

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:HP:3
P2GROUNDARENAUNIT:0:HP:2
P4GROUNDARENAUNIT:0:HP:2

---

# TeamSuns_TwoInterceptorsOnOneTeam_Stack
#// Each teammate may control their own copy of this unique unit. "Other" is by IDENTITY, not by name:
#// each Interceptor is an "other friendly Republic unit" to its teammate's copy (3 -> 4 HP), and a
#// Clone Trooper under both reads 2 + 1 + 1 = 4.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1SpaceArena: IC27_034:1:0
WithP3SpaceArena: IC27_034:1:0
WithP1GroundArena: TWI_241:1:0

## WHEN

## EXPECT
P1SPACEARENAUNIT:0:HP:4
P3SPACEARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:0:HP:4

---

# TwinSuns_FarSeatRepublicUnit_IsNotFriendly
#// Twin Suns (4 seats, no teams): every other seat is an opponent. P3 shares P1's team PARITY, so this
#// pins that "friendly" is the team only in a TEAM game — never seat parity, never "every seat".

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1SpaceArena: IC27_034:1:0
WithP1GroundArena: TWI_241:1:0
WithP3GroundArena: TWI_241:1:0

## WHEN

## EXPECT
SEATCOUNT:4
P1GROUNDARENAUNIT:0:HP:3
P3GROUNDARENAUNIT:0:HP:2
