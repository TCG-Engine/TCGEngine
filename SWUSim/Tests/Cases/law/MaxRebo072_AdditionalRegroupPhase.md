# LAW_072 Max Rebo — "There is an additional regroup phase after the first regroup phase each round."
# While Max Rebo is in play there are TWO regroup phases: both players draw 2 cards in EACH, so each
# player draws 4 total this round (a normal single regroup draws only 2). The round still advances just
# once (the round counter increments on the final regroup, not per regroup phase).
# Both players start with empty hands; after both regroups (declining each resource step) each has 4.

## GIVEN
P1LeaderBase: JTL_002/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: LAW_072:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP2Deck: SEC_080
WithP2Deck: SEC_080
WithP2Deck: SEC_080
WithP2Deck: SEC_080

## WHEN
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1HANDCOUNT:4
P2HANDCOUNT:4
PHASE:MAIN
