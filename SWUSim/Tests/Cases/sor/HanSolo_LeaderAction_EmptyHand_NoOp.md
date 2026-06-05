# SOR_017 Han Solo — Leader Action requires a card in hand to put into play as a resource.
# With an empty hand there is nothing to resource, so the action is a complete no-op:
# Han stays ready, resources unchanged, and the player keeps their action (no decision pending).

## GIVEN
P1LeaderBase: SOR_017/SOR_024
P2LeaderBase: SOR_014/SOR_024
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1RESCOUNT:2
P1RESAVAILABLE:2
P1NODECISION
