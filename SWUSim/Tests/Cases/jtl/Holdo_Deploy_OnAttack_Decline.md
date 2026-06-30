# JTL_007 Admiral Holdo (deployed leader unit) — the On Attack buff is optional ("You may"). P1 deploys
# Holdo, attacks, and DECLINES (AnswerDecision:-): JTL_099 keeps its printed 2/1.

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: JTL_099:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_099
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:1
P2BASEDMG:3
P1LEADER:DEPLOYED
