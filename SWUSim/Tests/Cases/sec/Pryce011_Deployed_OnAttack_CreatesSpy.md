# SEC_011 Governor Pryce (deployed) — On Attack: Create a Spy token. Deployed SEC_011 (4/6) attacks the
# enemy base; on attack it creates a Spy (SEC_T01) in P1's ground. The Spy enters exhausted, so it does
# not add to SEC_011's "+1/+0 per ready token" power (base damage = 4).

## GIVEN
P1LeaderBase: SEC_011:1:1:1/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_011:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SEC_T01
