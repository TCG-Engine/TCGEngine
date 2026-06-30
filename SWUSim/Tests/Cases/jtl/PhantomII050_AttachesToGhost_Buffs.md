# JTL_050 Phantom II — Action [1 resource]: attach it as an upgrade to The Ghost (JTL_053). It's no
# longer a unit; The Ghost gets +3/+3 and gains Grit. P1 activates Phantom II's action: it leaves the
# space arena and becomes an upgrade on The Ghost, which goes 5/6 → 8/9 and gains Grit. 1 resource spent.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1SpaceArena: JTL_050:1:0
WithP1SpaceArena: JTL_053:1:0

## WHEN
- P1>UseUnitAbility:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_053
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_050
P1SPACEARENAUNIT:0:POWER:8
P1SPACEARENAUNIT:0:HP:9
P1SPACEARENAUNIT:0:HASKEYWORD:Grit
P1RESAVAILABLE:1
