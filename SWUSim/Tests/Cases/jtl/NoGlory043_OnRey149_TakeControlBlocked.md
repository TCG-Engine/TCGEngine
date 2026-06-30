# JTL_043 No Glory, Only Results vs LAW_149 Rey — "Opponents can't take control of this unit." No Glory
# must take control BEFORE it defeats, so when the take-control is blocked there is no friendly unit to
# defeat — the whole effect fizzles and Rey stays under P2's control, undamaged and in play.

## GIVEN
CommonSetup: bbw/rrk/{myResources:13;handCardIds:JTL_043}
P1OnlyActions: true
WithP2GroundArena: LAW_149:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_149
P2DISCARDCOUNT:0
