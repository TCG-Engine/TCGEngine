# JTL_216 Contracted Hunter — When the regroup phase starts: Defeat this unit. P1 passes to end the
# action phase; at regroup start the Hunter is defeated and goes to the discard.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_216:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
