# VehiclesDealPower
#// JTL_129 Focus Fire — Each friendly Vehicle in the chosen unit's arena deals its power to it. P1's two
#// space Vehicles (SOR_237 + SOR_225, power 2 each = 4) defeat the enemy SOR_044 (3 HP).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_129
WithP1Resources: 10
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
