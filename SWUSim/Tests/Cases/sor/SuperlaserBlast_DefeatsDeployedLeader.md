# SOR_043 Superlaser Blast — "all units" includes a deployed leader unit, which is defeated and returns
# to its leader zone (NOTDEPLOYED). P1 deploys its leader, then plays Superlaser Blast.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_043
WithP1Resources: 13
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>DeployLeader
- P1>PlayHand:0

## EXPECT
P1LEADER:NOTDEPLOYED
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:0
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
