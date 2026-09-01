# Deployed_FriendlyAttackDefenderDebuff
#// SOR_018 Jyn Erso — Deployed: "While a friendly unit is attacking, the defender gets -1/-0."
#// Jyn is deployed (ground idx 1); a SEPARATE friendly 3/3 (idx 0) attacks P2's 3/7. The passive
#// reduces the defender's power 3 → 2, so the attacker takes 2 counter-damage (3 without it).
#// COVERAGE: offer=LeaderAction_Offer_ReadyFriendlyUnitsBothArenas (pending SELECTABLEEXACT on
#//           "attack with a unit": ready friendly units in BOTH arenas, exhausted friendly and all
#//           enemy units excluded) · reqboundary=SimulateRequestBoundary_AttackDefenderDebuffSurvives ·
#//           boundary pair=EpicDeploy_FiveResourcesBlocked (5) vs Deployed_FriendlyAttackDefenderDebuff
#//           (6) on the "6 or more resources" Epic threshold, and LeaderAction_DebuffFloorsAtZeroPower
#//           (1 power → 0, floors) vs LeaderAction_AttackDefenderDebuff (3 power → 2) on the -1/-0 ·
#//           decline=N/A — nothing on either side of this leader is printed as "you may": the leader
#//           Action's attack and its rider are unconditional once the ability is used, and the Epic
#//           deploy is a plain gate. The negatives that stand in for a decline branch are
#//           EpicDeploy_FiveResourcesBlocked (gate refused) and LeaderAction_AttackBase_
#//           NoDefenderToDebuff (rider with no defender to reduce) · control=N/A — the leader
#//           action tags the CHOSEN ATTACKER with a one-shot marker on the unit object and consumes
#//           it inside that same attack, and the deployed-side aura reads "a FRIENDLY unit is
#//           attacking" live from the controller's own board; neither stores a seat, so
#//           owner-vs-controller has nothing to strand, and a leader cannot change controller, so
#//           the who-resolves-it reading has no fixture either

## GIVEN
CommonSetup: gyw/brw/{
  myLeader:SOR_018;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:3
P1LEADER:DEPLOYED

---

# LeaderAction_AttackDefenderDebuff
#// SOR_018 Jyn Erso — Leader Action [Exhaust]: Attack with a unit. The defender gets -1/-0
#// for this attack. P1's 3/3 attacks P2's 3/7; the defender's power is reduced 3 → 2, so the
#// attacker takes only 2 counter-damage (3 without the debuff). The defender takes the full 3.

## GIVEN
CommonSetup: gyw/brw/{
  myLeader:SOR_018;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:3
P1LEADER:EXHAUSTED

---

# SimulateRequestBoundary_AttackDefenderDebuffSurvives
#// SOR_018 Jyn Erso — the leader action's defender pick ends the request in production, so the
#// "-1/-0 for this attack" grant that the action set up must be serialized rather than parked in a
#// transient global. Mirrors LeaderAction_AttackDefenderDebuff with a boundary before the answer: the
#// 3/7 defender still attacks back at 2, not 3.

## GIVEN
CommonSetup: gyw/brw/{
  myLeader:SOR_018;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:3
P1LEADER:EXHAUSTED

---

# EpicDeploy_FiveResourcesBlocked
#// SOR_018 Jyn Erso — Intended: "Epic Action: If you control 6 OR MORE resources, deploy this
#// leader." The negative half of the threshold, one resource short. With exactly 5 the Epic is not
#// available: Jyn stays in the leader row, the ground arena stays empty, no resource is spent and
#// the epic slot is not consumed. Positive half on the same board plus one resource:
#// Deployed_FriendlyAttackDefenderDebuff (WithP1Resources: 6). The pair pins the comparison at
#// "6 or more" rather than "more than 6".

## GIVEN
CommonSetup: gyw/brw/{
  myLeader:SOR_018;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:NOTDEPLOYED
P1LEADER:EPICAVAILABLE
P1GROUNDARENACOUNT:0
P1RESCOUNT:5
P1RESAVAILABLE:5

---

# LeaderAction_Offer_ReadyFriendlyUnitsBothArenas
#// SOR_018 Jyn Erso — Intended: the leader action's first pick is "Attack with A UNIT" — one of
#// YOUR units, and only a READY one (an exhausted unit cannot attack). The choose is left PENDING:
#// it must hold exactly the ready friendly ground unit and the ready friendly SPACE unit (the
#// action names no arena), and must exclude the EXHAUSTED friendly ground unit and every enemy
#// unit.

## GIVEN
CommonSetup: gyw/brw/{
  myLeader:SOR_018;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0    # ready friendly ground — legal
WithP1GroundArena: SOR_095:0:0    # EXHAUSTED friendly ground — must be excluded
WithP1SpaceArena: SOR_237:1:0     # ready friendly space — legal
WithP2GroundArena: SOR_046:1:0    # enemy unit — never an attacker for me

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0

---

# LeaderAction_DebuffFloorsAtZeroPower
#// SOR_018 Jyn Erso — Intended: -1/-0 reduces power, and power never goes below 0 (CR: a stat
#// cannot be reduced past zero). The defender is a 1/4 Warrior Drone: 1 - 1 = 0, so it survives the
#// attack (3 damage on 4 HP) but deals NO counter damage at all. Without the debuff the attacker
#// would be sitting at 1 damage — the 0 is what proves the reduction landed and floored.

## GIVEN
CommonSetup: gyw/brw/{
  myLeader:SOR_018;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: TWI_057:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER:EXHAUSTED

---

# LeaderAction_DebuffEndsAfterTheAttack
#// SOR_018 Jyn Erso — Intended duration edge: the debuff is "for THIS ATTACK", not for the phase.
#// The surviving 3/7 defender takes 3 and counters for 2 (3 - 1) during the attack, but once the
#// attack is over its power reads its printed 3 again. That end-state POWER:3 is the assertion the
#// sibling LeaderAction_AttackDefenderDebuff cannot make — it only measures the damage.

## GIVEN
CommonSetup: gyw/brw/{
  myLeader:SOR_018;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:DAMAGE:2
P1LEADER:EXHAUSTED

---

# LeaderAction_AttackBase_NoDefenderToDebuff
#// SOR_018 Jyn Erso — Intended: "Attack with a unit" is a normal attack, so the enemy BASE is a
#// legal attack target; the rider only speaks about "the defender", so when the defender is a base
#// there is nothing to reduce and the attack is ordinary. The 3/3 attacker hits P2's base for its
#// full 3, the bystander enemy unit is untouched and still reads its printed 3 power, and the
#// attacker takes nothing back.

## GIVEN
CommonSetup: gyw/brw/{
  myLeader:SOR_018;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER:EXHAUSTED
