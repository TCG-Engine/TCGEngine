# JTL_005 Admiral Piett (leader) — the action only plays a CAPITAL SHIP unit. With only a non-Capital
# Ship unit in hand (SOR_225 TIE Fighter), there is no eligible card, so the action fizzles: the leader
# exhausts, the hand is unchanged, and no card is played. Proves the Capital Ship restriction.

## GIVEN
CommonSetup: bgk/bbk/{
  myLeader:JTL_005;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_225
WithP1Resources: 4

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:1
P1LEADER:EXHAUSTED
P1NODECISION
