# SOR_016 Grand Admiral Thrawn — APS passive: looks at top of both decks at the start of the action phase.
#// The harness loads directly into MAIN, so ActionPhaseStart never fires on load. To exercise the
#// start-of-action-phase passive we PASS both players into a regroup and loop back to a NEW action
#// phase (READY -> APS), which fires ActionPhaseStart with Thrawn as leader → private REVEAL entries.
#// Decks hold 3 cards each so one card remains after the regroup's 2-card draw for the peek to see.

## GIVEN
CommonSetup: gyk/grw
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
