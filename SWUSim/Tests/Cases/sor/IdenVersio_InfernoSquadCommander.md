# Deploy_GetsShield
#// SOR_002 Iden Versio — Deploy: Shielded keyword gives Shield token on enter.
#// COVERAGE: offer=N/A (leader action and deployed reaction are both targetless — heal your own
#//           base; the deploy epic action is asserted in Deploy_GetsShield) · decline=N/A (the
#//           action may be used with no effect — LeaderAction_NoHeal asserts it still exhausts —
#//           and the deployed reaction is mandatory) · control=LeaderAction_OppStoleAndDefeatedMyUnit_Heals
#//           + LeaderAction_ITookControlAndDefeated_NoHeal + Deployed_OppStoleAndDefeatedMyUnit_Heals
#//           + Deployed_ITookControlAndDefeated_NoHeal (the heal keys on who CONTROLLED the unit at
#//           defeat, not who owned it) · boundary=LeaderAction_ResetNextPhase (the defeated-this-phase
#//           memory does not cross the phase boundary) · reqboundary=LeaderAction_HealBase +
#//           LeaderAction_FriendlyDefeatedOnly_NoHeal (the flag is written by the attack action and
#//           read by a later, separately-serialized leader action)

## GIVEN
CommonSetup: bbk/grk/{myResources:6}

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1LEADER:DEPLOYED
P1LEADER:EPICUSED

---

# LeaderAction_HealBase
#// SOR_002 Iden Versio — Leader Action: Heal Base
#// Enemy unit defeated this phase → heal 1 from P1 base.

## GIVEN
CommonSetup: bbk/grk/{myBaseDamage:3}
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithActivePlayer: 1
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:2:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility

## EXPECT
P1BASEDMG:2
P1LEADER:EXHAUSTED

---

# LeaderAction_NoHeal
#// SOR_002 Iden Versio — Leader Action: No Heal
#// No enemy defeated this phase → leader exhausts but base stays damaged.

## GIVEN
CommonSetup: bbk/grk/{myBaseDamage:3}

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1BASEDMG:3
P1LEADER:EXHAUSTED

---

# LeaderAction_FriendlyDefeatedOnly_NoHeal
#// SOR_002 Iden Versio (undeployed) — "If an ENEMY unit was defeated this phase". Only a FRIENDLY
#// unit died: P1's SOR_128 (3/1) attacks P2's SOR_046 (3/7) and dies to the counter damage while the
#// defender survives. The leader action still exhausts but heals nothing.

## GIVEN
CommonSetup: bbk/grk/{myBaseDamage:3}
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithActivePlayer: 1
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P1BASEDMG:3
P1LEADER:EXHAUSTED

---

# LeaderAction_ResetNextPhase
#// SOR_002 Iden Versio (undeployed) — the "was defeated THIS PHASE" memory resets at the phase
#// boundary. An enemy unit dies in the first action phase (mutual trade), the round ends, and the
#// leader action is used in the SECOND action phase → no heal (the leader re-readied at regroup and
#// exhausts again). Decks are seeded so the empty-deck regroup penalty never fires.

## GIVEN
CommonSetup: bbk/grk/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Deck: [SOR_046 SOR_046]
WithP2Deck: [SOR_046 SOR_046]

## WHEN
- P1>AttackGroundArena:0:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>UseLeaderAbility

## EXPECT
P1BASEDMG:3
P1LEADER:EXHAUSTED

---

# LeaderAction_OppStoleAndDefeatedMyUnit_Heals
#// SOR_002 Iden Versio (undeployed) — the heal keys on CONTROL AT DEFEAT. P2 plays JTL_043 (take
#// control of a non-leader unit, then defeat it) on P1's SOR_046: when it dies, P2 controls it, so
#// from P1's side an ENEMY unit was defeated this phase → the leader action heals 1 (base 3 → 2).
#// The defeated card still lands in its OWNER's (P1's) discard.

## GIVEN
CommonSetup: bbk/bbk/{myBaseDamage:3;theirResources:5}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SOR_046:1:0
WithP2Hand: JTL_043

## WHEN
- P2>PlayHand:0
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1BASEDMG:2
P1LEADER:EXHAUSTED

---

# LeaderAction_ITookControlAndDefeated_NoHeal
#// SOR_002 Iden Versio (undeployed) — the mirror case: P1 plays JTL_043 on P2's SOR_046. At the
#// moment it is defeated P1 controls it, so NO enemy unit was defeated from P1's side → the leader
#// action exhausts but heals nothing. The card goes to its owner's (P2's) discard.

## GIVEN
CommonSetup: bbk/grk/{myBaseDamage:3;myResources:5}
P1OnlyActions: true
WithP1Hand: JTL_043
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1BASEDMG:3
P1LEADER:EXHAUSTED

---

# Deployed_EnemyDefeated_Heals
#// SOR_002 Iden Versio (deployed unit side) — "When an enemy unit is defeated: Heal 1 damage from
#// your base." Deployed Iden (4/4) attacks and defeats P2's SOR_128 (3/1); the reaction heals 1
#// (base 3 → 2). Iden survives on 3 counter damage.

## GIVEN
CommonSetup: bbk/grk/{myBaseDamage:3;myLeaderDeployed:true}
P1OnlyActions: true
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENAUNIT:0:DAMAGE:3
P1BASEDMG:2

---

# Deployed_FriendlyDefeated_NoHeal
#// SOR_002 Iden Versio (deployed unit side) — a FRIENDLY defeat does not trigger her. P1's SOR_128
#// (3/1) attacks P2's SOR_046 (3/7) and dies to counter damage; the defender survives → no heal.
#// ⚠ myLeaderDeployed seats the leader unit AFTER the regular WithP1GroundArena lines, so SOR_128 is
#// ground index 0 and deployed Iden is index 1.

## GIVEN
CommonSetup: bbk/grk/{myBaseDamage:3;myLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:3
P1BASEDMG:3

---

# Deployed_OppStoleAndDefeatedMyUnit_Heals
#// SOR_002 Iden Versio (deployed unit side) — control at defeat decides. P2 plays JTL_043 on P1's
#// SOR_046: at defeat P2 controls it → from P1's side an enemy unit was defeated → deployed Iden's
#// reaction heals 1 immediately (base 3 → 2). Iden herself (a leader unit) is not a legal JTL_043
#// target, so the steal auto-resolves onto SOR_046.

## GIVEN
CommonSetup: bbk/bbk/{myBaseDamage:3;myLeaderDeployed:true;theirResources:5}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SOR_046:1:0
WithP2Hand: JTL_043

## WHEN
- P2>PlayHand:0
- P1>Drain

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1DISCARDCOUNT:1
P1BASEDMG:2

---

# Deployed_ITookControlAndDefeated_NoHeal
#// SOR_002 Iden Versio (deployed unit side) — P1 plays JTL_043 on P2's SOR_046: at defeat P1
#// controls it, so no ENEMY unit was defeated from P1's side → the deployed reaction does not fire
#// and the base stays at 3. The card lands in its owner's (P2's) discard.

## GIVEN
CommonSetup: bbk/grk/{myBaseDamage:3;myLeaderDeployed:true;myResources:5}
P1OnlyActions: true
WithP1Hand: JTL_043
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1BASEDMG:3

---

# SimulateRequestBoundary_EnemyDefeatedThisPhaseFlagSurvives
#// SOR_002 Iden Versio — the "an enemy unit was defeated this phase" memory is written by the attack
#// action and read by a LATER, separately-serialized leader action: in production the two actions are
#// different requests, so the flag must live in the gamestate and not a transient global. Mirrors
#// LeaderAction_HealBase with a request boundary between the attack and the leader action.

## GIVEN
CommonSetup: bbk/grk/{myBaseDamage:3}
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithActivePlayer: 1
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:2:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>SimulateRequestBoundary
- P1>UseLeaderAbility

## EXPECT
P1BASEDMG:2
P1LEADER:EXHAUSTED

---

# Deployed_TradeInCombat_IdenDiesToo_StillHeals
#// SOR_002 Iden Versio (deployed unit side) — ⚠ THE TRADE CELL. A deployed leader is a real arena unit:
#// it gets DEFEATED in combat and only then returns to its leader zone (user ruling 2026-08-17). Combat
#// damage is simultaneous, so the enemy was defeated while Iden was still in play and the heal must fire
#// even though Iden died in the same batch — the same simultaneous-defeat rule as Gideon/HK-47/Chimaera.
#// Deployed Iden (4/4) trades with LOF_084 Knight of Ren (4/4): 4 power each way kills both.
#// Contrast Deployed_EnemyDefeated_Heals above, where Iden survives — that section passes with a gate
#// that reads the live `Deployed` flag, which is already false here by the time the defeat is collected.
#// P1LEADER:EXHAUSTED is the other half of the ruling: she is back in the leader zone, exhausted.

## GIVEN
CommonSetup: bbk/grk/{myBaseDamage:3;myLeaderDeployed:true}
P1OnlyActions: true
WithP2GroundArena: LOF_084:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1LEADER:EXHAUSTED
P1BASEDMG:2
