# JTL_004 Rose Tico (deployed leader unit) — On Attack: You may heal 2 damage from a Vehicle unit
# (any Vehicle, no "attacked this phase" restriction). P1 deploys Rose (free epic, 5-resource
# threshold met), attacks P2's base (power 4), and on attack heals 2 from the damaged X-Wing (2 → 0).

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1SpaceArena: SOR_237:1:2

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:DAMAGE:0
P2BASEDMG:4
P1LEADER:DEPLOYED
P1LEADER:EPICUSED
