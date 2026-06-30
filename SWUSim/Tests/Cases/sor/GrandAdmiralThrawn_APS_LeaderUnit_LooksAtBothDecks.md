# SOR_016 Grand Admiral Thrawn — APS passive: fires when Thrawn is deployed as a leader unit.
# Thrawn in leader zone (Deployed=true) + unit on ground arena. APS private REVEAL entries appear for P1.

## GIVEN
CommonSetup: gyk/grw/{
  myLeader:SOR_016:1:1:1
}
SkipPreGame: true
WithP1Deck: SOR_095
WithP2Deck: SOR_128

## WHEN

## EXPECT
LOGCONTAINS: top of P1
LOGCONTAINS: top of P2
