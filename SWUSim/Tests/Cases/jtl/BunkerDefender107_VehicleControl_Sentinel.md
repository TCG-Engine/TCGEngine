# JTL_107 Bunker Defender — While you control a Vehicle unit, this unit gains Sentinel. With SOR_237 (a
# Vehicle) in play, JTL_107 has Sentinel.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_107:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_107
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
