# DefeatNonLeaderVehicle
#// JTL_078 Direct Hit (event) — Defeat a non-leader Vehicle unit. The only Vehicle (SOR_237) is defeated;
#// the non-Vehicle SEC_080 is not a legal target and survives.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_078
WithP1Resources: 4
WithP2SpaceArena: SOR_237:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
