# Front_GivesWeaknessToFriendlyUnit
#// HMW_003 Doctor Hemlock, Emotion Has No Place Here — Leader (Ground) 3/6, cost 6,
#// [Vigilance][Villainy], Imperial/Official.
#// FRONT: "Action [1 resource, Exhaust]: Give a Weakness token to a unit without a Weakness token on it."
#// Weakness (HMW_T02) is a -1/-1 Token Upgrade, so a 3/3 becomes 2/2 through the normal upgrade stat loop.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_003;myResources:3}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:HMW_T02
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:2

---

# Front_EnemyUnitIsALegalTarget
#// "a unit" carries NO friendly/enemy qualifier, so an enemy unit is a legal target (the
#// friendlyOnly-by-default trap). Only ONE unit exists, so the pick auto-resolves onto it —
#// which is itself the proof that the enemy was in the offered pool at all.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_003;myResources:3}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:HMW_T02
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:2

---

# Front_UnitWithWeaknessIsNotSelectable
#// THE LOAD-BEARING GATE: "without a Weakness token on it" must EXCLUDE an already-weakened unit
#// from the offer. Asserting the offer itself (not just the outcome) is the point — answering a
#// target proves the branch, never the pool.
#// THREE units, not two: index 0 carries a Weakness and must be excluded, leaving TWO legal targets.
#// With only one legal target left the choice AUTO-RESOLVES and there is no pending decision to
#// inspect, so the assertion fails with "no pending target-choice" and proves nothing.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_003;myResources:3}
P1OnlyActions: true
WithP1GroundArena: [SEC_080:1:0 SOR_128:1:0 SOR_046:1:0]
WithP1GroundArenaUpgrade: 0:HMW_T02

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SELECTABLEEXACT:myGroundArena-1&myGroundArena-2

---

# Front_PaysOneResourceAndExhaustsLeader
#// The bracketed cost is [1 resource, Exhaust]: exactly one resource is spent and the leader taps.
#// 3 ready resources in, 2 ready out.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_003;myResources:3}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1RESAVAILABLE:2
P1LEADER:EXHAUSTED

---

# Front_NoReadyResource_FullNoOp
#// Unaffordable cost = complete no-op: the leader must NOT exhaust, no token lands, and the player
#// keeps their action (no dangling decision).

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_003;myResources:0}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# Front_EveryUnitAlreadyWeakened_StillUsableAndFizzles
#// CR 6.4.587.c "use it anyway": the cost is state-changing ([1 resource, Exhaust]), so the Action
#// stays AVAILABLE with no legal target and simply fizzles — the cost is still paid. This matches
#// HMW_009 Chewbacca's front side in this same set. The negative partner to the no-op test above:
#// there the COST could not be paid; here it can, so the action happens and does nothing.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_003;myResources:3}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:HMW_T02

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1RESAVAILABLE:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION

---

# Front_WeaknessKillsOneHpUnit
#// Weakness is -1/-1, and there is no state-based defeat for "0 remaining HP" — GIVE_WEAKNESS runs
#// SWUCheckShrinkDefeats for exactly this. SOR_128 Death Star Stormtrooper is 3/1, so -1 HP defeats it.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_003;myResources:3}
P1OnlyActions: true
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1

---

# Epic_DeployAtSixResources
#// "Epic Action: If you control 6 or more resources, deploy this leader." The threshold equals the
#// leader's printed cost (6), which is the ENGINE DEFAULT — this card needs no deploy code at all.
#// The boundary partner below is what actually pins the number.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_003;myResources:6}
P1OnlyActions: true

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_003
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1LEADER:DEPLOYED
P1LEADER:EPICUSED

---

# Epic_BlockedAtFiveResources
#// Boundary partner: one under the threshold is a full no-op — no arena unit, Epic still available.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_003;myResources:5}
P1OnlyActions: true

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENACOUNT:0
P1LEADER:NOTDEPLOYED
P1LEADER:EPICAVAILABLE

---

# Deployed_OnAttack_MayGiveWeakness
#// DEPLOYED: "On Attack: You may give a Weakness token to a unit." A leader's two sides are separate
#// ability sets — this exercises the real deploy→attack dispatch, not a WithP1GroundArena stand-in.
#// Deployed Hemlock is 3/6; SOR_095 Battlefield Marine is 3/3 and dies to the 3 combat damage, so the
#// Weakness goes on the SURVIVING enemy at index 1.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_003;myResources:6}
P1OnlyActions: true
WithP2GroundArena: [SOR_095:1:0 SOR_046:1:0]

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:1
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:1:CARDID:SOR_046
P2GROUNDARENAUNIT:1:UPGRADECOUNT:1
P2GROUNDARENAUNIT:1:UPGRADE:0:CARDID:HMW_T02

---

# Front_EnemyDeployedLeaderIsALegalTarget
#// Value-CLASS variant: a deployed LEADER unit is still "a unit". This is a known trap — a target
#// collector filtering on the bare 'Unit' type silently excludes deployed leaders (their CardType is
#// 'Leader'), so a normal-unit test cannot prove this case. The enemy leader is the only unit on the
#// board, so the pick auto-resolves onto it.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_003;myResources:3;theirLeader:HMW_009;theirLeaderDeployed:true}
P1OnlyActions: true

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:ISLEADERUNIT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:HMW_T02

---

# Deployed_OnAttack_FiresWhenAttackingTheBase
#// Dispatch path: "On Attack" fires on ANY attack, not only an attack into a unit. A rider queued from
#// an OnAttack closure behaves differently on a base attack (there is no combat pause), so this is a
#// genuinely separate path from the unit-attack sections above.
#// AttackGroundArena:0:BASE forces the base even with enemy units present.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_003;myResources:6}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:HMW_T02

---

# Deployed_FriendlyUnitIsALegalTarget
#// The deployed side's "a unit" is unqualified too, so a FRIENDLY unit is a legal target — the mirror
#// of Front_EnemyUnitIsALegalTarget. Worth its own section because the two sides collect their targets
#// through different call paths (SWUOfferUnitTarget directly vs GiveTokenUpgrade).

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_003;myResources:6}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:2

---

# Deployed_OnAttack_Decline_NoToken
#// "You may" needs a real decline branch. MZMAYCHOOSE declines with `-`, NOT `NO`.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_003;myResources:6}
P1OnlyActions: true
WithP2GroundArena: [SOR_095:1:0 SOR_046:1:0]

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:1
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:1:UPGRADECOUNT:0

---

# Deployed_OnAttack_StacksOnAlreadyWeakenedUnit
#// THE ASYMMETRY BETWEEN THE TWO SIDES, and the reason they cannot share a target filter: the FRONT
#// says "a unit without a Weakness token on it", the DEPLOYED side just says "a unit". So the
#// deployed On Attack MAY target an already-weakened unit, stacking a second -1/-1.
#// SOR_046 Consular Security Force is 3/7; with two Weakness tokens it reads 1/5.

## GIVEN
CommonSetup: grw/grw/{myLeader:HMW_003;myResources:6}
P1OnlyActions: true
WithP2GroundArena: [SOR_095:1:0 SOR_046:1:0]
WithP2GroundArenaUpgrade: 1:HMW_T02

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:1
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:1:UPGRADECOUNT:2
P2GROUNDARENAUNIT:1:POWER:1
P2GROUNDARENAUNIT:1:HP:5
