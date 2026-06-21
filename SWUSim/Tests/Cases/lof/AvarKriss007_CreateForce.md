# LOF_007 Avar Kriss — Action [Exhaust]: The Force is with you (create your Force token). P1 starts without
# the Force and gains it.

## GIVEN
P1LeaderBase: LOF_007/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HASFORCE
P1LEADER:EXHAUSTED
