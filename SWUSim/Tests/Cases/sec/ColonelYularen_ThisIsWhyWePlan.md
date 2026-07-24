# Deployed_CompletesAttack_ChainCostLE4
#// SEC_006 Colonel Yularen (deployed) — When this unit completes an attack (and survives): You may attack
#// with another unit that costs 4 or less. Deployed SEC_006 (4/6) attacks the enemy base, then chains
#// SOR_095 (cost 2 ≤ 4, power 3). 4 + 3 = 7 base damage.

## GIVEN
CommonSetup: bgk/bbk/{
  myLeader:SEC_006:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:1
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:7
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# LeaderAction_AttackThenCheaper
#// SEC_006 Colonel Yularen (leader) — Action [Exhaust]: Attack with a unit. Then, you may attack with
#// another unit that costs less than it. P1 attacks with SOR_095 (cost 2, power 3) into the enemy base,
#// then chains SOR_128 (cost 1 < 2, power 3) into the base too. 3 + 3 = 6 base damage.

## GIVEN
CommonSetup: bgk/bbk/{
  myLeader:SEC_006;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_128:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:6
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:EXHAUSTED
P1LEADER:EXHAUSTED

---

# LeaderAction_PassSecondAttack
#// SEC_006 Colonel Yularen (leader) — the second attack is optional. P1 attacks with Battlefield Marine
#// (SOR_095) into P2's base (3), then declines the follow-up attack. Warrior Drone... i.e. the other unit
#// (SOR_128) stays ready.
## GIVEN
CommonSetup: bgk/bbk/{myLeader:SEC_006;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_128:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:-
## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:READY
P1LEADER:EXHAUSTED

---

# Deployed_PassSecondAttack
#// SEC_006 Colonel Yularen (deployed) — the chained attack is optional. Deployed Yularen (4/6) attacks
#// P2's base (4), then declines to chain a second unit; Battlefield Marine (SOR_095) stays ready.
## GIVEN
CommonSetup: bgk/bbk/{myLeader:SEC_006:1:1:1;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:1
- P1>AnswerDecision:-
## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:EXHAUSTED
P1LEADER:DEPLOYED

---

# Deployed_NoLegalChainTarget_NoOffer
#// SEC_006 Colonel Yularen (deployed) — with the only other unit already exhausted, there is no legal
#// unit to chain, so no second attack is offered. Yularen attacks P2's base (4) and completes.
## GIVEN
CommonSetup: bgk/bbk/{myLeader:SEC_006:1:1:1;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
## WHEN
- P1>AttackGroundArena:1
## EXPECT
P2BASEDMG:4
P1NODECISION
P1LEADER:DEPLOYED

---

# Deployed_YularenDiesDuringAttack_NoSecondAttack
#// SEC_006 Colonel Yularen (deployed) — the chain requires Yularen to SURVIVE the attack. He attacks
#// Reinforcement Walker (SOR_119, 6/9): he deals 4 (it survives) and is defeated by the 6 counter-damage.
#// Because he did not complete-and-survive, no second attack is offered; Battlefield Marine stays ready.
## GIVEN
CommonSetup: bgk/bbk/{myLeader:SEC_006:1:1:1;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_119:1:0
## WHEN
- P1>AttackGroundArena:1:0
## EXPECT
P1LEADER:NOTDEPLOYED
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:READY
P1NODECISION
