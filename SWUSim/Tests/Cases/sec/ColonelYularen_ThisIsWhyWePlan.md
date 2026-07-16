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
