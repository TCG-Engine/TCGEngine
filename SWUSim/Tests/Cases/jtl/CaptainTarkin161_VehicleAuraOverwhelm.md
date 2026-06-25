# JTL_161 Captain Tarkin — Each friendly Vehicle unit gets +1/+0 and gains Overwhelm. SOR_237 (a
# Vehicle, 2/3) becomes power 3 with Overwhelm; Tarkin itself (not a Vehicle) stays 2 power.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_161:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_161
P1GROUNDARENAUNIT:0:POWER:2
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:HASKEYWORD:Overwhelm
