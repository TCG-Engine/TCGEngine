# Deployed_OnAttack_Deal2Exhausted
#// SEC_014 Sly Moore (deployed) — On Attack: You may deal 2 damage to an exhausted unit. Deployed SEC_014
#// (3/6) attacks the enemy base; On Attack → deal 2 to the exhausted enemy SOR_095 (3/3 → DAMAGE:2).

## GIVEN
CommonSetup: byk/bbk/{
  myLeader:SEC_014:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_095:0:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# LeaderAction_FourExhausted_CreatesSpy
#// SEC_014 Sly Moore (leader) — Action [1 resource, Exhaust]: If there are 4 or more exhausted units in
#// play, create a Spy token. Four exhausted units (2 P1 + 2 P2) → a Spy (SEC_T01) enters P1's ground.

## GIVEN
CommonSetup: byk/bbk/{
  myLeader:SEC_014;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SOR_095:0:0
WithP1GroundArena: SOR_095:0:0
WithP2GroundArena: SOR_095:0:0
WithP2GroundArena: SOR_095:0:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:2:CARDID:SEC_T01
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED
