# AdditionalRegroupPhase
#// LAW_072 Max Rebo — "There is an additional regroup phase after the first regroup phase each round."
#// While Max Rebo is in play there are TWO regroup phases: both players draw 2 cards in EACH, so each
#// player draws 4 total this round (a normal single regroup draws only 2). The round still advances just
#// once (the round counter increments on the final regroup, not per regroup phase).
#// Both players start with empty hands; after both regroups (declining each resource step) each has 4.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
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

---

# BothPlayers_Stacks
#// LAW_072 Max Rebo — the "additional regroup phase" effect STACKS: each Max Rebo in play adds one
#// additional regroup phase. With BOTH players controlling a Max Rebo there are 2 additional regroups
#// (3 total), so each player draws 2 per regroup → 6 total. The round still advances only once (on the
#// final regroup). The 6-ResourcePass sequence reaches the next action phase (MAIN).

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

---

# NoMaxRebo_SingleRegroup_Control
#// LAW_072 control — WITHOUT Max Rebo in play, there is exactly ONE regroup phase: each player draws 2.
#// Same setup as MaxRebo072_AdditionalRegroupPhase but with a vanilla unit instead of Max Rebo, so only
#// one regroup runs (one resource step per player) and each player ends with 2 cards.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
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
