# SEC_011 Governor Pryce (deployed) — On Attack: Create a Spy token. Deployed SEC_011 (4/6) attacks the
# enemy base; on attack it creates a Spy (SEC_T01) in P1's ground. The Spy enters exhausted, so it does
# not add to SEC_011's "+1/+0 per ready token" power (base damage = 4).

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:SEC_011:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SEC_T01
