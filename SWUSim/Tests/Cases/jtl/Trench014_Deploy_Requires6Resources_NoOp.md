# JTL_014 Admiral Trench — the deploy action requires controlling 6 or more resources (separate from
# the 3-resource cost). With only 5 resources P1 cannot deploy: DeployLeader is a no-op, Trench stays
# in leader form, and the 5 resources are untouched.

## GIVEN
P1LeaderBase: JTL_014/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 5
WithP1Deck: SOR_095
WithP1Deck: SOR_237

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:NOTDEPLOYED
P1RESAVAILABLE:5
P1DECKCOUNT:2
