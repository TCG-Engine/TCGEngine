# SEC_239 Viper Probe Droid (Ground, 3/2, cost 2) — When Played: look at an opponent's hand.
#   Informational only — the card enters play and the opponent's hand is unchanged.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
P1OnlyActions: true
WithP2Hand: SOR_095
WithP1Hand: SEC_239

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P2HANDCOUNT:1
