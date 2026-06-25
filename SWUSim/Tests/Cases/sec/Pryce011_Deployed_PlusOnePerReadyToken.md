# SEC_011 Governor Pryce (deployed) — This unit gets +1/+0 for each ready friendly token unit. Deployed
# SEC_011 (4/6) + two ready Battle Droid tokens → power 4 + 2 = 6, proven by attacking the enemy base for 6.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:SEC_011:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0

## WHEN
- P1>AttackGroundArena:2:BASE

## EXPECT
P2BASEDMG:6
