# JTL_070 U-Wing Lander — the move is a "may": declining leaves the upgrade on the U-Wing.
# Same setup as the move test; P1 declines the upgrade MZMAYCHOOSE, so SOR_120 stays on JTL_070.

## GIVEN
CommonSetup: bbw/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_070:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:SOR_120

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:4
P1SPACEARENAUNIT:0:CARDID:JTL_070
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:SOR_120
P1SPACEARENAUNIT:1:CARDID:SOR_237
P1SPACEARENAUNIT:1:UPGRADECOUNT:0
