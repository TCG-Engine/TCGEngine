# JTL_204 Home One — If an opponent controls 3 or more space units, this unit costs 3 resources less.
# With P2 controlling 3 space units, the cost-9 Home One plays for 6, consuming exactly 6 resources.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:JTL_016;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_204
WithP1Resources: 6
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_204
P1RESAVAILABLE:0
