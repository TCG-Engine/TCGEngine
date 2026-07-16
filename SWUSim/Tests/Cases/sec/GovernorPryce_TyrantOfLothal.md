# Deployed_OnAttack_CreatesSpy
#// SEC_011 Governor Pryce (deployed) — On Attack: Create a Spy token. Deployed SEC_011 (4/6) attacks the
#// enemy base; on attack it creates a Spy (SEC_T01) in P1's ground. The Spy enters exhausted, so it does
#// not add to SEC_011's "+1/+0 per ready token" power (base damage = 4).

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

---

# Deployed_PlusOnePerReadyToken
#// SEC_011 Governor Pryce (deployed) — This unit gets +1/+0 for each ready friendly token unit. Deployed
#// SEC_011 (4/6) + two ready Battle Droid tokens → power 4 + 2 = 6, proven by attacking the enemy base for 6.

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

---

# LeaderAction_ReadyToken
#// SEC_011 Governor Pryce (leader) — Action [1 resource, Exhaust]: Ready a token unit. P1's exhausted
#// Battle Droid token (TWI_T01) is readied.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:SEC_011;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: TWI_T01:0:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P1GROUNDARENAUNIT:0:READY
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED
