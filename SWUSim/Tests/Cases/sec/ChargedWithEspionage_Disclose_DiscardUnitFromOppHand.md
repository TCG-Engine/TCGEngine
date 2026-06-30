# SEC_230 Charged with Espionage (Event, Cunning) — "You may disclose CunningCunning → look at an
#   opponent's hand and discard a UNIT from it." Opp hand: SOR_095 (unit) + SEC_074 (event). Disclose
#   SEC_220 + SEC_233 (Cunning each) → the unit filter offers only SOR_095 → it's discarded; the event stays.

## GIVEN
CommonSetup: yyk/grw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SEC_230
WithP1Hand: SEC_220
WithP1Hand: SEC_233
WithP2Hand: SOR_095
WithP2Hand: SEC_074

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0&myHand-1

## EXPECT
P2HANDCOUNT:1
P2DISCARDCOUNT:1
P1NODECISION
