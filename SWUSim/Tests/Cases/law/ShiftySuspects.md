# OnAttackNoBaseHeal
#// LAW_197 Shifty Suspects (4/5) — On Attack: bases can't be healed for this phase. Shifty attacks the
#// base (setting the lock); then Tantive IV (Restore 2) attacks the base but its Restore is blocked, so
#// P1's base (pre-damaged 3) stays at 3.

## GIVEN
CommonSetup: rrw/bgw/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: LAW_197:1:0
WithP1SpaceArena: LAW_109:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:3

---

# NoBaseHealLock_SurvivesTheRequestBoundary
#// LAW_197 Shifty Suspects — request-boundary guard on the "bases can't be healed for this phase" lock, which
#// by definition has to outlive the action that set it. Shifty attacks the base (setting the lock); SOR_142
#// Sabine then attacks SEC_081 Major Partagaz, leaving a REAL pending choose for her On Attack damage
#// (MZMAYCHOOSE over theirGroundArena-0 & theirBase-0 & myBase-0); a serialize round-trip is inserted before
#// that answer. Only then does LAW_109 Tantive IV (Restore 2) attack the base — its Restore is still blocked,
#// so P1's pre-damaged base stays at 3. Control run (identical fixture without Shifty's attack): the base
#// heals to 1, so the 3 here is load-bearing.

## GIVEN
CommonSetup: rrw/bgw/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: [LAW_197:1:0 SOR_142:1:0]
WithP1SpaceArena: LAW_109:1:0
WithP2GroundArena: SEC_081:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AttackGroundArena:1:theirGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:3

---

# LockExpiresNextActionPhase
#// LAW_197 Shifty Suspects — "Bases can't be healed for THIS PHASE". The lock has to end with the phase,
#// so the same Restore 2 attack that healed nothing while the lock was live heals normally once the next
#// action phase begins: P1's base goes from 3 damage to 1. Both decks are seeded so the regroup draws add
#// no CR 6.1 empty-deck damage on top.
#// The pass chain starts with P2: P1's attack IS its action, so the turn is already with P2 when the
#// passing begins. P2's base damage is asserted alongside to prove the second attack really happened —
#// 4 from the ground attack in phase one plus 5 from the Tantive IV in phase two.

## GIVEN
CommonSetup: rrw/bgw/{myBaseDamage:3}
WithP1GroundArena: LAW_197:1:0
WithP1SpaceArena: LAW_109:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:1
P2BASEDMG:9

---

# NoAttack_NoLock_RestoreHealsNormally
#// LAW_197 Shifty Suspects — the control that gives OnAttackNoBaseHeal its meaning. Identical board, but
#// Shifty Suspects never attacks, so no lock is applied and the Tantive IV's Restore 2 heals P1's base
#// from 3 damage to 1. Without this half, a Restore that had simply failed for an unrelated reason would
#// read as the lock working.

## GIVEN
CommonSetup: rrw/bgw/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: LAW_197:1:0
WithP1SpaceArena: LAW_109:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:1
