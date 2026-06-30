# JTL_005 Admiral Piett (leader) — Action [Exhaust]: Play a Capital Ship unit from your hand. It costs
# 1 resource less. P1 plays JTL_069 Munificent Frigate (cost 5, Vigilance — covered by the Vigilance
# base) for 5 − 1 = 4, leaving 0 resources.

## GIVEN
CommonSetup: bgk/bbk/{
  myLeader:JTL_005;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_069
WithP1Resources: 4

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_069
P1RESAVAILABLE:0
P1HANDCOUNT:0
P1LEADER:EXHAUSTED
