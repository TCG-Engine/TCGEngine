# JTL_044 Echo Base Engineer — the Shield only targets a DAMAGED Vehicle. With an undamaged Vehicle in
# play, there is no legal target and no Shield is granted (no decision pending).

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_044
WithP1Resources: 2
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:SHIELDCOUNT:0
P1NODECISION
