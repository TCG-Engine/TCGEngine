# Front_ReadiesAnExhaustedCreature_AndDealsOne
#// HMW_012 Poggle the Lesser, Let the Executions Begin — Leader, [Aggression][Villainy], Separatist/
#// Official, cost 5 (deployed 1/6 Ground).
#//   FRONT     Action [1 resource, Exhaust]: Ready a friendly Creature unit and deal 1 damage to it.
#//             Epic Action: If you control 5 or more resources, deploy this leader.
#//   DEPLOYED  When Deployed: Create a Beast token.
#//             On Attack: You may ready a friendly Creature unit and deal 1 damage to it.
#//
#// COVERAGE: offer=Front_Offer_FriendlyCreaturesOnly_ReadyOnesIncluded + Deployed_OnAttack_Offer
#//           decline=Deployed_OnAttack_Decline (the front has no "may" — its target is mandatory; the
#//                   no-target soft pass is Front_NoFriendlyCreature_SoftPass_CostStillPaid)
#//           boundary=N/A (structural — the 1 damage and the one Beast are fixed; no threshold or count)
#//           control=N/A (structural — a leader can't be taken control of, and "friendly" is read per
#//                   Creature at resolution; the Team Suns section covers a Creature you don't control)
#//           reqboundary=Front_AcrossARequestBoundary + Deployed_OnAttack_AcrossARequestBoundary
#//           modes=2P,TeamSuns (text says "a FRIENDLY Creature unit" — a teammate's Creature is friendly:
#//                 Front_TeamSuns_ATeammatesCreatureIsALegalPick) · TwinSuns=N/A (no player reference)
#//           epic-deploy=N/A (generic — SWUDeployLeader gates on the printed cost, 5)
#//
#// ★ "Ready … AND deal 1 damage to it" — joined by "and", not "If you do", so the damage is NOT gated on
#// the ready happening. And per CR 1.e a READY Creature is still a legal choice for a readying effect
#// (it just isn't "readied"). Both halves are pinned: Front_AlreadyReadyCreature_StillTakesTheOne and
#// Front_CantReady_StillTakesTheOne.
#//
#// This section: the only friendly Creature is an exhausted HMW_083 Batcher (2/3), so the mandatory pick
#// auto-resolves. It is readied and takes 1. Poggle is exhausted and the 1 resource is spent.

## GIVEN
CommonSetup: rrk/bbw/{myResources:1;myLeader:HMW_012:1}
P1OnlyActions: true
WithP1GroundArena: HMW_083:0:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_083
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:DAMAGE:1
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
P1NODECISION

---

# Front_Offer_FriendlyCreaturesOnly_ReadyOnesIncluded
#// The pool: FRIENDLY units with the CREATURE trait — exhausted or ready (CR 1.e), token or not.
#//   myGroundArena-0  Batcher, exhausted Creature          → in
#//   myGroundArena-1  Beast token (HMW_T03), READY Creature → in
#//   myGroundArena-2  SOR_128, exhausted, not a Creature    → out
#//   theirGroundArena-0  an ENEMY Beast token               → out
#// Left pending so the pool itself is the assertion.

## GIVEN
CommonSetup: rrk/bbw/{myResources:1;myLeader:HMW_012:1}
P1OnlyActions: true
WithP1GroundArena: [HMW_083:0:0 HMW_T03:1:0 SOR_128:0:0]
WithP2GroundArena: HMW_T03:0:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# Front_AlreadyReadyCreature_StillTakesTheOne
#// CR 1.e: a ready Creature can be chosen; it does not change orientation. "…and deal 1 damage to it" is
#// not an "If you do", so it still takes the 1.

## GIVEN
CommonSetup: rrk/bbw/{myResources:1;myLeader:HMW_012:1}
P1OnlyActions: true
WithP1GroundArena: [HMW_083:0:0 HMW_T03:1:0]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:HMW_T03
P1GROUNDARENAUNIT:1:READY
P1GROUNDARENAUNIT:1:DAMAGE:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# Front_CantReady_StillTakesTheOne
#// The other half of "and": SHD_193 Frozen in Carbonite ("Attached unit can't ready") blocks the ready,
#// and the 1 damage still lands.

## GIVEN
CommonSetup: rrk/bbw/{myResources:1;myLeader:HMW_012:1}
P1OnlyActions: true
WithP1GroundArena: HMW_083:0:0
WithP1GroundArenaUpgrade: 0:SHD_193

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:1
P1LEADER:EXHAUSTED

---

# Front_NoFriendlyCreature_SoftPass_CostStillPaid
#// No friendly Creature: the Action's cost (1 resource + exhaust) is state-changing, so it is still a
#// legal action and resolves to nothing — the non-Creature is untouched, the cost is paid, no prompt.

## GIVEN
CommonSetup: rrk/bbw/{myResources:1;myLeader:HMW_012:1}
P1OnlyActions: true
WithP1GroundArena: SOR_128:0:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
P1NODECISION

---

# Front_NoResource_CannotBeUsed
#// The [1 resource] half of the cost: with nothing to pay it, the Action is not available — Poggle stays
#// ready and the Creature is untouched.

## GIVEN
CommonSetup: rrk/bbw/{myResources:0;myLeader:HMW_012:1}
P1OnlyActions: true
WithP1GroundArena: HMW_083:0:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# Front_AcrossARequestBoundary
#// Two legal Creatures, so the pick is a real prompt and the boundary sits between the Action and the
#// answer.

## GIVEN
CommonSetup: rrk/bbw/{myResources:1;myLeader:HMW_012:1}
P1OnlyActions: true
WithP1GroundArena: [HMW_083:0:0 HMW_T03:0:0]

## WHEN
- P1>UseLeaderAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:READY
P1GROUNDARENAUNIT:1:DAMAGE:1
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# Front_TurnPassesExactlyOnce
#// No P1OnlyActions: the turn really alternates. The Action closes once, after the ready-and-damage.

## GIVEN
CommonSetup: rrk/bbw/{myResources:1;myLeader:HMW_012:1}
WithActivePlayer: 1
WithP1GroundArena: [HMW_083:0:0 HMW_T03:0:0]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:READY
TURNPLAYER:2

---

# Front_TeamSuns_ATeammatesCreatureIsALegalPick
#// TEAM SUNS: "friendly" is the TEAM. Seat 3 is P1's teammate, so its exhausted Beast is in the pool
#// beside P1's own Batcher; the opponents' Beasts (seats 2 and 4) are not. P1 readies the teammate's.

## GIVEN
CommonSetup: rrk/bbw/{myResources:1;myLeader:HMW_012:1}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: HMW_083:0:0
WithP3GroundArena: HMW_T03:0:0
WithP2GroundArena: HMW_T03:0:0
WithP4GroundArena: HMW_T03:0:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
SEATCOUNT:4
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&p3GroundArena-0

---

# Deployed_WhenDeployed_CreatesABeast
#// The real deploy path: Poggle deploys (free — the threshold is a condition) and his When Deployed
#// creates a Beast token (HMW_T03, 3/3 Creature), which enters exhausted like any created token.

## GIVEN
CommonSetup: rrk/bbw/{myResources:5;myLeader:HMW_012}
P1OnlyActions: true

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:HMW_012
P1GROUNDARENAUNIT:1:CARDID:HMW_T03
P1GROUNDARENAUNIT:1:EXHAUSTED
P1RESAVAILABLE:5
P1NODECISION

---

# Deployed_OnAttack_ReadiesTheBeast_AndDealsOne
#// The deployed side's combo, end to end: deploy (a Beast arrives exhausted), then Poggle attacks the base
#// and his On Attack readies that Beast with 1 damage on it. Poggle is 1 power: the base takes 1.

## GIVEN
CommonSetup: rrk/bbw/{myResources:5;myLeader:HMW_012}
P1OnlyActions: true

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:1
P1GROUNDARENAUNIT:1:CARDID:HMW_T03
P1GROUNDARENAUNIT:1:READY
P1GROUNDARENAUNIT:1:DAMAGE:1
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# Deployed_OnAttack_Decline
#// The printed "You may": declining leaves the Beast exhausted and undamaged.

## GIVEN
CommonSetup: rrk/bbw/{myResources:5;myLeader:HMW_012}
P1OnlyActions: true

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:1
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:1:DAMAGE:0
P1NODECISION

---

# Deployed_OnAttack_Offer
#// The deployed pool is the same as the front's: friendly Creatures only. Poggle is pre-deployed (index
#// 2, after the seeded units): the exhausted Batcher and the ready Beast are in; SOR_128 and the enemy
#// Beast are out, and so is Poggle himself (a Separatist, not a Creature).

## GIVEN
CommonSetup: rrk/bbw/{myResources:5;myLeader:HMW_012:1:1}
P1OnlyActions: true
WithP1GroundArena: [HMW_083:0:0 HMW_T03:1:0 SOR_128:0:0]
WithP2GroundArena: HMW_T03:0:0

## WHEN
- P1>AttackGroundArena:3:BASE

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# Deployed_OnAttack_NoCreature_NoPrompt
#// Nothing to ready: no prompt at all (a "may" that could only fizzle is never offered), and the attack
#// resolves normally.

## GIVEN
CommonSetup: rrk/bbw/{myResources:5;myLeader:HMW_012:1:1}
P1OnlyActions: true
WithP1GroundArena: SOR_128:0:0

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P2BASEDMG:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION

---

# Deployed_OnAttack_AcrossARequestBoundary
#// The On Attack pick is answered in a fresh process.

## GIVEN
CommonSetup: rrk/bbw/{myResources:5;myLeader:HMW_012}
P1OnlyActions: true

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:1
P1GROUNDARENAUNIT:1:READY
P1GROUNDARENAUNIT:1:DAMAGE:1

---

# Deployed_OnAttack_DeclineWithPASS_TurnPassesOnce
#// The deployed On Attack shares its continuation with the front Action (HMW_012#0, which closes the action
#// only for the front). A literal "PASS" decline must not strand or double the close: no P1OnlyActions (it
#// would hide a double turn swap) — P1 deploys, P2 passes, P1 attacks and declines with PASS, and the turn
#// is P2's exactly once. The Beast stays exhausted and undamaged.

## GIVEN
CommonSetup: rrk/bbw/{myResources:5;myLeader:HMW_012}
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>DeployLeader
- P2>Pass
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:PASS

## EXPECT
P2BASEDMG:1
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:1:DAMAGE:0
TURNPLAYER:2
