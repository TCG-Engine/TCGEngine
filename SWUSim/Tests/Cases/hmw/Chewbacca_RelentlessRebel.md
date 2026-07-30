# Front_AttacksWithAnExhaustedUnit
#// HMW_009 front — "Action [2 resources, Exhaust]: Attack with a unit, even if it's exhausted. It can't
#// attack bases for this attack." SOR_095 (3/3) is EXHAUSTED, so only this ability can make it attack.
#// It hits SOR_063 (2/4 Sentinel, the only enemy unit): defender takes 3 and survives, countering 2 back.
#// Resources 3 - 2 = 1 proves the cost, and the leader exhausts as its own cost.

## GIVEN
CommonSetup: ggw/ggw/{myLeader:HMW_009:1;myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:0
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED

---

# Front_CantAttackBases_NoEnemyUnitFizzles
#// The "It can't attack bases for this attack" half. The friendly unit here is READY, so a normal attack
#// would auto-fire at the enemy base — the ability must offer no attacker at all and fizzle instead.
#// Cost is still paid (the leader exhausts, resources spent): a fizzled effect doesn't refund a cost.

## GIVEN
CommonSetup: ggw/ggw/{myLeader:HMW_009:1;myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:0
P1GROUNDARENAUNIT:0:READY
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED
P1NODECISION

---

# Front_Unaffordable_FullNoOp
#// Only 1 ready resource against the [2 resources] cost — the whole action must no-op: the leader stays
#// READY (the player keeps their action), the resource is unspent, and nothing attacks.

## GIVEN
CommonSetup: ggw/ggw/{myLeader:HMW_009:1;myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1RESAVAILABLE:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# Deployed_AttacksWithAnExhaustedUnit_NoResourceCost
#// The DEPLOYED side is a separate ability: "Action: Attack with a unit, even if it's exhausted. ... only
#// once each round" — no resource cost and no self-exhaust. Driven through the REAL deploy→unit-action
#// path. Both the exhausted SOR_095 and the freshly-deployed (ready) Chewbacca are legal attackers, so
#// the pick is a real choose; we send SOR_095. Resources stay at 5 (deploy is free, the Action is free)
#// and the leader unit stays READY (its cost is not Exhaust).

## GIVEN
CommonSetup: ggw/ggw/{myLeader:HMW_009;myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>DeployLeader
- P1>UseUnitAbility:myGroundArena-1
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:HMW_009
P1GROUNDARENAUNIT:1:READY
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:0
P1RESAVAILABLE:5

---

# Deployed_OnlyOnceEachRound
#// "Use this ability only once each round." The second activation in the same round must be a complete
#// no-op. Without the limit SOR_095 would attack again (being exhausted is no obstacle for THIS ability),
#// and 3 + 3 damage would defeat the 4-HP SOR_063 — so the surviving defender at DAMAGE:3 is the proof.

## GIVEN
CommonSetup: ggw/ggw/{myLeader:HMW_009:1:1;myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-1
- P1>AnswerDecision:myGroundArena-0
- P1>UseUnitAbility:myGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION
