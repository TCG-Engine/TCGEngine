# JTL_011 Major Vonreg (leader) — the +1/+0 is given to ANOTHER unit. With no other unit in play after
# the Vehicle enters, the buff has no target and fizzles: the played TIE keeps its printed 2 power.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:JTL_011;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_225
WithP1Resources: 1

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:POWER:2
P1LEADER:EXHAUSTED
