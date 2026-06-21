# LAW_072 control — WITHOUT Max Rebo in play, there is exactly ONE regroup phase: each player draws 2.
# Same setup as MaxRebo072_AdditionalRegroupPhase but with a vanilla unit instead of Max Rebo, so only
# one regroup runs (one resource step per player) and each player ends with 2 cards.

## GIVEN
P1LeaderBase: JTL_002/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SEC_080:1:0
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

## EXPECT
P1HANDCOUNT:2
P2HANDCOUNT:2
PHASE:MAIN
