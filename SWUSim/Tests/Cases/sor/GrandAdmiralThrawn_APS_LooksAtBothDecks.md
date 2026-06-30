# SOR_016 Grand Admiral Thrawn — APS passive: looks at top of both decks when deployed.
# ActionPhaseStart fires on load (APS->MAIN transition). Thrawn logs private REVEAL entries.

## GIVEN
CommonSetup: gyk/grw
SkipPreGame: true
WithP1Deck: SOR_095
WithP2Deck: SOR_128

## WHEN

## EXPECT
LOGCONTAINS: top of P1
LOGCONTAINS: top of P2
