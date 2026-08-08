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

---

# LeaderAction_NoUnitsAtAll_StillUsable_JustExhausts
#// SEC_006 Colonel Yularen (leader) — the Action is usable even with nothing to attack with. Paying the
#// cost (exhausting the leader) changes the game state, so the ability may be activated and simply has
#// no effect (CR 6.4.587.c). P1 controls no units: the leader still exhausts, no attack happens, and no
#// decision is left dangling.

## GIVEN
CommonSetup: bgk/bbk/{
  myLeader:SEC_006;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P2BASEDMG:0
P1GROUNDARENACOUNT:0
P1NODECISION

---

# LeaderAction_OnAttackTriggerFiresOnTheFIRSTAttackOfTheChain
#// SEC_006 Colonel Yularen (leader) — the chained attacks are real attacks, so a unit's On Attack triggers
#// normally in either slot. Here the trigger is on the FIRST attacker: SOR_142 Sabine Wren (cost 2)
#// attacks P2's SpecForce Soldier and her "On Attack: you may deal 1 damage to the defender or to a base"
#// is offered — P1 sends it at P2's base. Combat then kills the 2/2 Soldier and leaves Sabine on 2 damage.
#// P1 chains SOR_128 (cost 1, less than 2) into the base for 3, so P2's base ends on 1 + 3 = 4.

## GIVEN
CommonSetup: bgk/bbk/{myLeader:SEC_006;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_142:1:0
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_140:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:4
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_142
P1GROUNDARENAUNIT:0:DAMAGE:2
P1LEADER:EXHAUSTED

---

# LeaderAction_OnAttackTriggerFiresOnTheCHAINEDSecondAttack
#// SEC_006 Colonel Yularen (leader) — the mirror: the trigger is on the SECOND, chained attacker. SOR_046
#// (cost 4) attacks P2's base for 3, then P1 chains SOR_142 Sabine Wren (cost 2, less than 4) into the
#// SpecForce Soldier; her On Attack is offered on that chained attack too and adds 1 to P2's base
#// (3 + 1 = 4). Together with the section above this shows the trigger fires in either slot, not just on
#// the attack that starts the chain.

## GIVEN
CommonSetup: bgk/bbk/{myLeader:SEC_006;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_142:1:0
WithP2GroundArena: SOR_140:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:4
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:1:CARDID:SOR_142
P1GROUNDARENAUNIT:1:DAMAGE:2
P1LEADER:EXHAUSTED

---

# Deployed_StacksWithAhsokaTano_ThreeAttacksInOneAction
#// SEC_006 Colonel Yularen (deployed) + SEC_096 Ahsoka Tano — two separate "attack with another unit"
#// grants CHAIN off each other, giving three attacks in a single action.
#// Ahsoka (2 power) attacks the enemy base. On completing it she discloses CommandHeroism (SOR_095
#// Battlefield Marine, Command/Heroism) and grants "attack with another unit" — spent on the DEPLOYED
#// YULAREN (4 power), who is a legal choice alongside the Death Trooper. Yularen completing THAT attack
#// fires his own deployed chain, "attack with another unit that costs less than it" (less than 5), and
#// the only ready unit left is SOR_033 Death Trooper (cost 3, 3 power).
#// Base damage 2 + 4 + 3 = 9, and all three attackers end exhausted.
#// The deployed leader is appended AFTER the seated units, so the arena is 0=Ahsoka, 1=Death Trooper,
#// 2=Yularen.

## GIVEN
CommonSetup: bgk/bbk/{
  myLeader:SEC_006:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_096:1:0
WithP1GroundArena: SOR_033:1:0
WithP1Hand: SOR_095

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myGroundArena-2
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:9
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:2:EXHAUSTED
P1NODECISION
