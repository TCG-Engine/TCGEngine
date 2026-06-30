# SEC_006 Colonel Yularen (deployed) — When this unit completes an attack (and survives): You may attack
# with another unit that costs 4 or less. Deployed SEC_006 (4/6) attacks the enemy base, then chains
# SOR_095 (cost 2 ≤ 4, power 3). 4 + 3 = 7 base damage.

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
