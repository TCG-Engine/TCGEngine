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

---

# HealsTheBaseOfWhoeverCONTROLSTheFlagbearer
#// COVERAGE: offer=N/A (nothing is targeted — the heal is automatic and its recipient is named by the
#//           ability) · reqboundary=AmbushFirstAttack_SurvivesTheRequestBoundary ·
#//           control=HealsTheBaseOfWhoeverCONTROLSTheFlagbearer +
#//           OpponentControlledFlagbearerHealsTHEIRBase · boundary=FirstAttackHealBase vs
#//           NoHealIfEnemyAttackedThisPhase (phase's first attack / not) · decline=N/A (mandatory
#//           triggered ability — no "you may").
#// LAW_112 — "heal 2 damage from YOUR base": "your" is the Flagbearer's CONTROLLER, not its owner. The
#// Flagbearer here sits in P1's ground arena but is OWNED by P2 (the end state after a control-take), and
#// the two bases carry DIFFERENT damage totals so the 2 points of healing are readable on one side only.
#// Its own attack is the phase's first, so P1's base heals 5 -> 3 while P2's base merely takes the 1 point
#// of combat damage (7 -> 8). An owner-scoped heal would have pulled 2 damage off P2's base instead, and
#// every existing section — where P1 both owns and controls the Flagbearer — would pass either way.

## GIVEN
CommonSetup: bbw/bgw/{myBaseDamage:5; theirBaseDamage:7}
P1OnlyActions: true
WithP1GroundArenaControlled: LAW_112:2

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:3
P2BASEDMG:8

---

# OpponentControlledFlagbearerHealsTHEIRBase
#// LAW_112 — the mirror, and the sharper half: P1 OWNS the Flagbearer but P2 CONTROLS it (it sits in P2's
#// ground arena). The heal follows control, so it is P2's base that heals 7 -> 5, and P1's base is not
#// healed at all — it simply takes the 1 point of combat damage (5 -> 6). Reading "your base" off the
#// owner would have moved those 2 points onto P1's base and left P2's at 7, which is exactly the failure
#// this section exists to catch.

## GIVEN
CommonSetup: bbw/bgw/{myBaseDamage:5; theirBaseDamage:7}
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithActivePlayer: 2
WithP2GroundArenaControlled: LAW_112:1

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5
P1BASEDMG:6
