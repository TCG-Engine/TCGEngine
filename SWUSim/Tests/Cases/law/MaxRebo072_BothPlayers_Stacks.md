# LAW_072 Max Rebo — the "additional regroup phase" effect STACKS: each Max Rebo in play adds one
# additional regroup phase. With BOTH players controlling a Max Rebo there are 2 additional regroups
# (3 total), so each player draws 2 per regroup → 6 total. The round still advances only once (on the
# final regroup). The 6-ResourcePass sequence reaches the next action phase (MAIN).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: LAW_072:1:0
WithP2GroundArena: LAW_072:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP2Deck: SEC_080
WithP2Deck: SEC_080
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
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1HANDCOUNT:6
P2HANDCOUNT:6
PHASE:MAIN
