# SOR_041 Power of the Dark Side — when the opponent controls exactly ONE unit the choice is forced,
# so it is defeated directly with no decision queued (a fragile cross-player auto-resolve is avoided).

## GIVEN
P1LeaderBase: SOR_002/SOR_021
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_041
WithP1Resources: 3
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P2GROUNDARENACOUNT:0
