# ExpToDamaged
#// SOR_037 Academy Defense Walker (5/5) — When Played: give an Experience token to
#// each friendly DAMAGED unit. P1's damaged Battlefield Marine (SOR_095, 1 damage) gets
#// +1/+1 (power 3 → 4); the undamaged Consular Security Force (SOR_046) gets nothing.
#// COVERAGE: offer=N/A (the When Played chooses nothing — it sweeps every friendly damaged unit; the
#//           absence of any decision is asserted by NoDamagedUnitsAtAll_NothingHappensAndNothingIsAsked
#//           via P1NODECISION) ·
#//           decline=N/A (no "you may" on either clause; the tokens are mandatory) ·
#//           control=StolenDamagedUnit_IsFriendlyByCONTROL_AndGetsExperience (a P2-owned unit under P1
#//           control is friendly and is served, while the enemy damaged unit in the same section is not)
#//           + EnemyDamagedUnits_GetNothing (the plain-ownership negative) ·
#//           boundary pair=ExpToDamaged (1 damage is served, 0 damage is not) +
#//           AlreadyExperiencedDamagedUnit_GetsExactlyOneMoreToken (exactly one more token, never two) ·
#//           reqboundary=N/A (the ability resolves entirely inside the play — it queues no decision, so
#//           no state is written on one request and read on another; the granted Experience is an
#//           ordinary upgrade whose round-trip is covered by the shared upgrade serialization)

## GIVEN
CommonSetup: bbk/bbk/{myResources:6;handCardIds:SOR_037}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:1    # damaged → gets Experience — index 0
WithP1GroundArena: SOR_046:1:0    # undamaged → no Experience — index 1

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:1:POWER:3

---

# Sentinel_EnemyCannotReachTheBasePastTheWalker
#// Intended: "Sentinel (Units in this arena can't attack your non-Sentinel units or your base.)" — the
#// keyword half of the card, which the When Played sections never touch. P2's Wampa (4/5) declares an
#// attack on P1's base while the Walker (5/5, Sentinel) and a non-Sentinel Battlefield Marine are both
#// in the ground arena: the base takes NOTHING and the Marine is untouched. The Walker absorbs the 4
#// and its own 5 power defeats the Wampa outright, which is why P2's arena empties.

## GIVEN
CommonSetup: bbk/bbk
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SOR_037:1:0    # Academy Defense Walker (Sentinel) — index 0
WithP1GroundArena: SOR_095:1:0    # non-Sentinel Battlefield Marine — index 1
WithP2GroundArena: SOR_164:1:0    # Wampa 4/5

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:0
P1GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENACOUNT:0

---

# Sentinel_ProtectsOnlyItsOwnArena_SpaceAttackGoesThrough
#// Intended: "Units in THIS arena" — Sentinel is arena-scoped. The load-bearing negative of the section
#// above: the very same Walker is on the ground, but P2's attacker is an Alliance X-Wing (2/3) in
#// SPACE, so nothing stops it and P1's base takes its full 2. The Walker is not even damaged. Without
#// this, a Sentinel implemented board-wide would still pass the positive test.

## GIVEN
CommonSetup: bbk/bbk
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SOR_037:1:0    # Academy Defense Walker (Sentinel) — GROUND
WithP2SpaceArena: SOR_237:1:0     # Alliance X-Wing 2/3 — SPACE

## WHEN
- P2>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:2
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# EnemyDamagedUnits_GetNothing
#// Intended: "each FRIENDLY damaged unit". The controller gate is load-bearing — an ENEMY damaged unit
#// with identical damage must be skipped. P1's damaged Battlefield Marine (1 damage) goes 3/3 → 4/4
#// with one Experience token; P2's damaged Battlefield Marine, same card and same 1 damage, keeps
#// power 3 and no upgrade at all. Without the enemy copy, an implementation sweeping every damaged
#// unit on the board would pass the original ExpToDamaged section.

## GIVEN
CommonSetup: bbk/bbk/{myResources:6;handCardIds:SOR_037}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:1    # friendly damaged — index 0
WithP2GroundArena: SOR_095:1:1    # ENEMY damaged — must be skipped

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# DamagedSpaceUnitAlsoGetsExperience_NoArenaRestriction
#// Intended: "each friendly damaged unit" names no arena, so the Walker's own ground arena is not the
#// boundary. P1's damaged Alliance X-Wing (2/3, 1 damage) in SPACE gains the token and reads 3/4; the
#// undamaged X-Wing beside it stays 2/3, keeping the damaged/undamaged gate honest in the other arena
#// as well.

## GIVEN
CommonSetup: bbk/bbk/{myResources:6;handCardIds:SOR_037}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:1     # damaged space unit — index 0
WithP1SpaceArena: SOR_237:1:0     # undamaged space unit — index 1

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:HP:4
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:1:POWER:2
P1SPACEARENAUNIT:1:UPGRADECOUNT:0

---

# NoDamagedUnitsAtAll_NothingHappensAndNothingIsAsked
#// Intended: the no-valid-target case. Every friendly unit is undamaged (and the Walker itself enters
#// at a clean 5/5), so the When Played hands out nothing at all and, since the ability chooses no
#// targets, it must not stop for a prompt either. Two undamaged friendlies plus the Walker end with
#// zero upgrades between them and no pending decision.

## GIVEN
CommonSetup: bbk/bbk/{myResources:6;handCardIds:SOR_037}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1GROUNDARENAUNIT:2:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:2:POWER:5
P1NODECISION

---

# DamagedDeployedLeaderUnit_CountsAsAFriendlyDamagedUnit
#// Intended: a deployed leader is a unit in the arena, so a damaged one is a "friendly damaged unit"
#// and gets an Experience token like any other. Iden Versio (SOR_002) is deployed with 2 damage and
#// reads 4/4; after the Walker is played she is 5/5 with one upgrade. She is the only unit on the board
#// when the Walker is played, so she sits at index 0 and the freshly-played, undamaged Walker at
#// index 1 with nothing attached.

## GIVEN
CommonSetup: bbk/bbk/{myResources:6;handCardIds:SOR_037;myLeader:SOR_002:1:1:0:2}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:CARDID:SOR_037
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0

---

# AlreadyExperiencedDamagedUnit_GetsExactlyOneMoreToken
#// Intended: the Walker gives "AN Experience token" — one per damaged unit per play, and Experience
#// tokens stack. The Battlefield Marine already carries one Experience token (so it reads 4/4 with 1
#// damage before the play) and comes out of the play at 5/5 with TWO upgrades — not one (a
#// "give it Experience if it has none" implementation) and not three (a per-token loop).

## GIVEN
CommonSetup: bbk/bbk/{myResources:6;handCardIds:SOR_037}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:1        # damaged Battlefield Marine — index 0
WithP1GroundArenaUpgrade: 0:SOR_T01   # ...already holding one Experience token

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2

---

# StolenDamagedUnit_IsFriendlyByCONTROL_AndGetsExperience
#// Intended: "friendly" is decided by CONTROL, not ownership. P1 controls a Consular Security Force
#// (3/7) that P2 still OWNS. It attacks P2's Industrious Team (4/7) and comes back with 4 damage, so by
#// the time the Walker is played it is a damaged unit on P1's side of the board — and it gets the
#// Experience token, reading 4/8 with 4 damage. The enemy Industrious Team is damaged too (3 from the
#// swing) and gets nothing, which is the same-section negative.
#// (A `Controlled` unit seats before the Walker is played, so it is index 0 and the Walker index 1.)

## GIVEN
CommonSetup: bbk/bbk/{myResources:6;handCardIds:SOR_037}
P1OnlyActions: true
WithP1GroundArenaControlled: SOR_046:2    # P1 CONTROLS it, P2 OWNS it
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:8
P1GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:CARDID:SOR_037
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
