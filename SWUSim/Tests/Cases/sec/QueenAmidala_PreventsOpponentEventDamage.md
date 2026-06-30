# SEC_101 Queen Amidala — OPPONENT EVENT (ability damage). P1 plays Contempt for Culture (SEC_246, "deal
#   2 to a non-Vehicle unit") targeting P2's Amidala. The ability-damage funnel defers and offers P2 the
#   prevention; P2 defeats its Official SEC_118 → Amidala takes 0. (Proves the SWUDealDamageToUnit path.)

## GIVEN
CommonSetup: rrk/ggw/{myResources:2;handCardIds:SEC_246}
P1OnlyActions: true
WithP2GroundArena: SEC_101:1:0
WithP2GroundArena: SEC_118:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_101
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:1
