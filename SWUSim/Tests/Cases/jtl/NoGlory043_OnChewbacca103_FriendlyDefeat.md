# JTL_043 No Glory, Only Results vs JTL_103 Chewbacca — "This unit can't be defeated by enemy card
# abilities." No Glory takes control FIRST, so by the time it defeats Chewbacca he's friendly to P1 —
# the immunity (which only blocks ENEMY defeats) no longer applies, and he is defeated to P2's discard.

## GIVEN
CommonSetup: bbw/rrk/{myResources:13;handCardIds:JTL_043}
P1OnlyActions: true
WithP2GroundArena: JTL_103:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
