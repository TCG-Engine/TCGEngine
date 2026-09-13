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

---

# Front_AttackerOfferIncludesEXHAUSTEDUnits_AndOnlyFriendlyOnes
#// THE OFFER ITSELF, and the clause that makes it unusual. "Attack with a unit, EVEN IF IT'S EXHAUSTED"
#// deliberately overrides the ready requirement that gates every ordinary attacker pool — so the pool
#// must contain exhausted friendly units, which no other section here reads directly (they answer it and
#// assert the attack happened, which a pool missing one of the two would still satisfy).
#// It also must NOT contain enemy units: "a unit" here is the attacker, resolved from the acting
#// player's own arenas.
#// Board: one READY and one EXHAUSTED friendly unit plus an enemy to attack — both friendlies come back,
#// the enemy does not.
## GIVEN
CommonSetup: ggw/ggw/{myLeader:HMW_009:1;myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 SOR_046:0:0]
WithP2GroundArena: SOR_063:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# RequestBoundary_TheAttackerChoiceSurvives
#// The request-boundary cell. The leader Action pays 2 resources and exhausts the leader, then queues the
#// attacker choose — so the paid cost AND the "can't attack bases for this attack" marker both have to
#// survive a fresh request before the attack resolves. A marker held in memory is empty next request and
#// the usual symptom is a bases-attack that should have been refused.
#// Two eligible attackers keep the choose interactive; the exhausted one is picked, exercising the
#// "even if it's exhausted" clause across the boundary too.
## GIVEN
CommonSetup: ggw/ggw/{myLeader:HMW_009:1;myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 SOR_046:0:0]
WithP2GroundArena: SOR_063:1:0
## WHEN
- P1>UseLeaderAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:1:DAMAGE:2
P2BASEDMG:0
P1LEADER:EXHAUSTED

---

# Front_AttackerPool_ExcludesCantAttackUnits_AndUnitsWithNoUnitTarget
#// The attacker OFFER's two exclusions. "Attack with a unit … It can't attack bases for this attack" —
#// a unit is only a legal attacker if it has a legal NON-BASE target:
#//   · LOF_044 Loth-Wolf ("This unit can't attack.") is never offered, ready or not.
#//   · SOR_141 Green Squadron A-Wing is in SPACE and P2 has no space unit — with bases forbidden it has
#//     nothing to attack, so it is not offered either.
#// Offered: the ready Wampa and the EXHAUSTED AT-ST (the "even if it's exhausted" half). Left PENDING.

## GIVEN
CommonSetup: ggw/ggw/{myLeader:HMW_009:1;myResources:3}
P1OnlyActions: true
WithP1GroundArena: [SOR_164:1:0 LOF_044:1:0 SOR_232:0:0]
WithP1SpaceArena: SOR_141:1:0
WithP2GroundArena: [SOR_046:1:0 SOR_202:1:0]

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-2

---

# Front_DefenderPool_IsEnemyUnitsOnly_NoBase
#// "It can't attack bases for this attack" read at the DEFENDER pick. Every other front section has a
#// single enemy unit, so the defender auto-resolves and the base's absence from the pool is never seen.
#// Two non-Sentinel enemy units: the pool is exactly those two — P2's base is not on it. The lone
#// attacker (Wampa) auto-resolves.

## GIVEN
CommonSetup: ggw/ggw/{myLeader:HMW_009:1;myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: [SOR_046:1:0 SOR_202:1:0]

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED

---

# Front_NoBaseRestriction_EndsWithThatAttack
#// DURATION: "It can't attack bases FOR THIS ATTACK." The restriction dies with the attack. The Wampa
#// attacks via the leader ability (4 into SOR_046 3/7, 3 back), then SHD_182 Bravado readies that SAME
#// Wampa and it makes an ordinary attack into P2's base — which must be allowed. A no-bases marker keyed
#// on the unit (rather than scoped to the ability's attack) would refuse it.
#// ⚠ FIXTURE: Bravado is Aggression cost 5 (no enemy defeated this phase → no discount); the red base
#//   covers Aggression, so 2 (ability) + 5 = 7 resources.

## GIVEN
CommonSetup: rgw/ggw/{myLeader:HMW_009:1;myResources:7}
P1OnlyActions: true
WithP1Hand: SHD_182
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: [SOR_046:1:0 SOR_202:1:0]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:DAMAGE:3
P2BASEDMG:4
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# Deployed_AttackerPool_IncludesChewbaccaHimself
#// The DEPLOYED side's attacker offer. The leader unit is itself a friendly unit, so it is a legal
#// attacker for its own ability, alongside the ready Wampa and the exhausted AT-ST. Same two exclusions
#// as the front: Loth-Wolf (can't attack) and the A-Wing (space, no enemy space unit, bases forbidden).
#// ⚠ The deployed leader seats at the END of the ground arena: myGroundArena-3. Left PENDING.

## GIVEN
CommonSetup: ggw/ggw/{myLeader:HMW_009:1:1;myResources:5}
P1OnlyActions: true
WithP1GroundArena: [SOR_164:1:0 LOF_044:1:0 SOR_232:0:0]
WithP1SpaceArena: SOR_141:1:0
WithP2GroundArena: [SOR_046:1:0 SOR_202:1:0]

## WHEN
- P1>UseUnitAbility:myGroundArena-3

## EXPECT
P1GROUNDARENAUNIT:3:CARDID:HMW_009
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-2&myGroundArena-3

---

# Deployed_DefenderPool_IsEnemyUnitsOnly_NoBase
#// The deployed side's "can't attack bases" at the DEFENDER pick: attacker Wampa chosen, pool is exactly
#// the two enemy units. The deployed side's own coverage above only ever had one enemy unit.

## GIVEN
CommonSetup: ggw/ggw/{myLeader:HMW_009:1:1;myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: [SOR_046:1:0 SOR_202:1:0]

## WHEN
- P1>UseUnitAbility:myGroundArena-1
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1
P1RESAVAILABLE:5

---

# Deployed_OnceEachRound_RefreshesNextRound
#// The other half of "only once each round": the budget comes BACK in the next round. Round 1: the
#// deployed ability sends the EXHAUSTED SOR_046 (3/7) into SOR_063 (2/4 Sentinel) — 3 dealt, 2 back.
#// Regroup. Round 2: the ability is usable again and the same attack defeats the Wing Guard (3 + 3 ≥ 4);
#// SOR_046 has now taken 2 + 2 = 4 and survives. A once-per-GAME limit (or a use flag never cleared at
#// regroup) leaves the second activation a no-op and the Wing Guard alive at 3.
#// ⚠ Both decks seeded (the section crosses regroup). Round 2's first action belongs to P2 (initiative).

## GIVEN
CommonSetup: ggw/ggw/{myLeader:HMW_009:1:1;myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_046:0:0
WithP2GroundArena: SOR_063:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>UseUnitAbility:myGroundArena-1
- P1>AnswerDecision:myGroundArena-0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>UseUnitAbility:myGroundArena-1
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:1:CARDID:HMW_009
