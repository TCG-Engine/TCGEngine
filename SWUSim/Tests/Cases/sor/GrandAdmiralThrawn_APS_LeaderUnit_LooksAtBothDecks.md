# SOR_016 Grand Admiral Thrawn — APS passive fires when Thrawn is deployed as a leader unit.
#// Thrawn deployed (leader zone Deployed=true, linked ground-arena leader unit). Same as the leader-side
#// test: PASS both players into a regroup and loop back to a NEW action phase (READY -> APS) to fire
#// ActionPhaseStart → the deck peek logs private REVEAL entries. Decks hold 3 cards so one survives the draw.

## GIVEN
CommonSetup: gyk/grw/{
  myLeader:SOR_016:1:1:1
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP2Deck: SOR_128
WithP2Deck: SOR_128
WithP2Deck: SOR_128

## WHEN
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
LOGCONTAINS: top of P1
LOGCONTAINS: top of P2
PHASE:MAIN
