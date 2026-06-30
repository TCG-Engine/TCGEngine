# JTL_005 Admiral Piett (deployed leader unit) — passive: Each Capital Ship unit you play costs 2
# resources less. P1 deploys Piett (free epic, 5-resource threshold met) then plays JTL_069 Munificent
# Frigate (cost 5, Vigilance covered by the Vigilance base) for 5 − 2 = 3, leaving 2 of 5 resources.

## GIVEN
CommonSetup: bgk/bbk/{
  myLeader:JTL_005;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_069
WithP1Resources: 5

## WHEN
- P1>DeployLeader
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_069
P1RESAVAILABLE:2
P1LEADER:DEPLOYED
P1LEADER:EPICUSED
