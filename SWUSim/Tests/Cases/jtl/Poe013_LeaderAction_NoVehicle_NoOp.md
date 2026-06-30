# JTL_013 Poe Dameron (LEADER) — Leader Action guard: no eligible Vehicle → no-op.
# No friendly Vehicles present. Leader stays ready. No decision queued.
# Also covers the 0-ready-resource guard: if resources < 1, SWULeaderActionAffordable returns false.

## GIVEN
CommonSetup: grw/grw/{
  myLeader:JTL_013;
  myBase:SOR_022;
  theirLeader:JTL_013;
  theirBase:SOR_022
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 1

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:0
P1LEADER:READY
P1LEADER:EPICAVAILABLE
P1NODECISION
