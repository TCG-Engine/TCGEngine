# LOF_007 Avar Kriss — Action [Exhaust]: The Force is with you (create your Force token). P1 starts without
# the Force and gains it.

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:LOF_007;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HASFORCE
P1LEADER:EXHAUSTED
