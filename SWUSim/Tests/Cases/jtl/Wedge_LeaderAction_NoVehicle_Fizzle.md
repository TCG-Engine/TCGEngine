# JTL_008 Wedge Antilles (leader) — with no friendly Vehicle in play there is no valid Piloting host,
# so the pilot in hand is not playable via Piloting and the action fizzles: the leader exhausts, the
# pilot stays in hand, and no card is attached.

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:JTL_008;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_108
WithP1Resources: 3

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HANDCOUNT:1
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:0
P1LEADER:EXHAUSTED
P1NODECISION
