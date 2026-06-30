# JTL_010 Captain Phasma (deployed leader unit) — On Attack: If you played another First Order card
# this phase, you may deal 1 damage to a unit. If you do, deal 1 damage to a base. P1 deploys Phasma,
# plays JTL_081 (First Order), then attacks P2's base: on attack it deals 1 to SOR_095 (→1 damage) and
# 1 to the enemy base; combat then adds Phasma's power 4 → P2 base 5 total.

## GIVEN
CommonSetup: grk/bbk/{
  myLeader:JTL_010;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: JTL_081
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>DeployLeader
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:1
P2BASEDMG:5
P1LEADER:DEPLOYED
