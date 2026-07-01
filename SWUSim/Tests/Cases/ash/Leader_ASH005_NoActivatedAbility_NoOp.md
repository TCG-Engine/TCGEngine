# ASH_005 Luke Skywalker (LEADER) — front side is purely REACTIVE ("When a friendly unit's
# attack ends: you may exhaust this leader..."), so it has NO activated leader action.
# Clicking the leader (UseLeaderAbility) must be a no-op: the leader stays ready, nothing queued.
# Regression: SWULeaderActionAffordable used to return true for any zero-cost leader with no
# $leaderAbilities entry, so ASH_005 glowed and clicking it exhausted (tapped) the leader for free.

## GIVEN
CommonSetup: grw/grw/{
  myLeader:ASH_005;
  myBase:SOR_022;
  theirLeader:ASH_005;
  theirBase:SOR_022
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 3

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1NODECISION
