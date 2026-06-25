# JTL_087 TIE Ambush Squadron — When Played: Create a TIE Fighter token. Playing it leaves the squadron
# plus one TIE Fighter in the space arena.

## GIVEN
CommonSetup: ggk/bbk/{
  myLeader:JTL_005;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_087
WithP1Resources: 8

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:2
