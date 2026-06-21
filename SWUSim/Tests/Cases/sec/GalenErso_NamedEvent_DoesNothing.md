# SEC_046 Galen Erso — naming an EVENT denies its ability, so playing it does nothing (it still pays
# its cost and goes to discard). P1 names "I Am the Senate" (SEC_092, "Create 5 Spy tokens"). P2 plays
# it, but no Spy tokens are created — P2's board stays empty and the event lands in P2's discard.

## GIVEN
CommonSetup: bbw/ggk
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2Resources: 10
WithP2Hand: SEC_092

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:I Am the Senate
- P2>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
