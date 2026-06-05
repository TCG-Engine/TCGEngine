# SOR_167 Force Throw — choosing the OPPONENT routes the discard to them (they discard their own card;
# here their only card, SOR_128, auto-resolves). P1 controls no Force unit, so the "may deal damage"
# half is skipped. P2's hand empties; only Force Throw itself is in P1's discard.

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP2Hand: SOR_128
WithP1Hand: SOR_167

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P2HANDCOUNT:0
P2DISCARDCOUNT:1
P1DISCARDCOUNT:1
