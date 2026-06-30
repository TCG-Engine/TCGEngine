# JTL_090 Executor — When Played: Create 3 TIE Fighter tokens. Playing it leaves the Executor plus three
# TIE Fighters (4 units) in the space arena.

## GIVEN
CommonSetup: ggk/bbk/{
  myLeader:JTL_005;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_090
WithP1Resources: 15

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:4
