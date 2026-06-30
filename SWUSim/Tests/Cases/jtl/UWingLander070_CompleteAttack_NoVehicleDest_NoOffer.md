# JTL_070 U-Wing Lander — the destination must be another friendly VEHICLE. With only a friendly
# non-Vehicle unit (SOR_095 Battlefield Marine, a Trooper) available, there is no eligible destination,
# so completing the attack offers no decision and the upgrade stays on the U-Wing.

## GIVEN
CommonSetup: bbw/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_070:1:0
WithP1SpaceArenaUpgrade: 0:SOR_120
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:4
P1NODECISION
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:SOR_120
P1GROUNDARENACOUNT:1
