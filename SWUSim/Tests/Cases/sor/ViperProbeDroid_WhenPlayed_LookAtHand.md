# SOR_228 Viper Probe Droid (Unit, cost 2, Villainy) — "When Played: Look at an opponent's hand."
# Pure information: P1 plays Viper; P2's hand (2 cards) is shown to P1 as an acknowledge popup
# (card images + an OK button) and logged. Nothing is discarded — P2's hand is unchanged.

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_228
WithP2Hand: SEC_080
WithP2Hand: SOR_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:OK

## EXPECT
P1GROUNDARENACOUNT:1
P2HANDCOUNT:2
P2DISCARDCOUNT:0
P1NODECISION
LOGCONTAINS:looked at
