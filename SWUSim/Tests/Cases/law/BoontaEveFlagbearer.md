# FirstAttackHealBase
#// LAW_112 Boonta Eve Flagbearer (1/3) — When a friendly unit attacks: if no other units have attacked
#// this phase, heal 2 from your base. SOR_046 (the first attacker) attacks the base; P1's base (damaged
#// 2) heals to 0.

## GIVEN
CommonSetup: bbw/bgw/{myBaseDamage:2}
P1OnlyActions: true
WithP1GroundArena: LAW_112:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P1BASEDMG:0

---

# BoontaItselfAttacksHeals
#// LAW_112 Boonta Eve Flagbearer — its own attack counts as "a friendly unit attacks". If nothing else has
#// attacked this phase, heal 2. Boonta (1/3) attacks the enemy base; P1's base (dmg 5) heals to 3.

## GIVEN
CommonSetup: bbw/bgw/{myBaseDamage:5}
P1OnlyActions: true
WithP1GroundArena: LAW_112:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:3

---

# NoHealIfEnemyAttackedThisPhase
#// LAW_112 Boonta Eve Flagbearer — "no OTHER units have attacked this phase (including enemy units)". An
#// enemy A-Wing (Raid 1) attacks P1's base for 2 first; when Boonta then attacks, no heal occurs. Base stays
#// at 5 + 2 = 7.

## GIVEN
CommonSetup: bbw/bgw/{myBaseDamage:5}
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithActivePlayer: 2
WithP1GroundArena: LAW_112:1:0
WithP2SpaceArena: SEC_213:1:0

## WHEN
- P2>AttackSpaceArena:0:BASE
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:7

---

# NonAttackActionBeforeStillHeals
#// LAW_112 Boonta Eve Flagbearer — a non-attack action does not count as "a unit attacked". The enemy
#// PLAYS a unit (a non-attack action) first, then Boonta attacks as the first ATTACKER of the phase: base
#// (dmg 5) still heals to 3.

## GIVEN
CommonSetup: bbw/bgw/{myBaseDamage:5}
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithActivePlayer: 2
WithP1GroundArena: LAW_112:1:0
WithP2Hand: SOR_046
WithP2Resources: 6

## WHEN
- P2>PlayHand:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:3

---

# AmbushAttackIsStillTheFirstAttack_Heals
#// An AMBUSH attack is an attack: when a friendly unit Ambushes as the phase's first attack, the
#// Flagbearer's "no other units have attacked this phase" condition holds and the base heals 2.
#// Intended: base at 5 damage heals to 3 when the played LAW_219 Ambush-attacks the enemy marine.

## GIVEN
CommonSetup: yyw/rrk/{myResources:3; myBaseDamage:5}
P1OnlyActions: true
WithP1GroundArena: LAW_112:1:0
WithP1Hand: LAW_219
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1BASEDMG:3

---

# AmbushFirstAttack_SurvivesTheRequestBoundary
#// LAW_112 Boonta Eve Flagbearer — request-boundary guard on the "no other units have attacked this phase"
#// tracker. Same flow as AmbushAttackIsStillTheFirstAttack_Heals, but a serialize round-trip is inserted
#// before the Ambush YES/NO answer: in production that answer arrives in a fresh process, so a phase tracker
#// held in a transient global would be lost and the heal would not fire. Base at 5 damage still heals to 3.

## GIVEN
CommonSetup: yyw/rrk/{myResources:3; myBaseDamage:5}
P1OnlyActions: true
WithP1GroundArena: LAW_112:1:0
WithP1Hand: LAW_219
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES

## EXPECT
P1BASEDMG:3
