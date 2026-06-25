# JTL_182 Rampart — This unit doesn't ready during the regroup phase unless its power is 4 or more.
# Rampart (3 power) starts exhausted and stays EXHAUSTED through regroup, while the control SOR_237
# readies normally.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1SpaceArena: JTL_182:0:0
WithP1SpaceArena: SOR_237:0:0

## WHEN
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_182
P1SPACEARENAUNIT:0:EXHAUSTED
P1SPACEARENAUNIT:1:CARDID:SOR_237
P1SPACEARENAUNIT:1:READY
