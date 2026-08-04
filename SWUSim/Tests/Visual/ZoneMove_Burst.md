# VISUAL CHECK — burst pacing (three slides at once)
#
# Visual-only schema. IC27_167 Lando Calrissian: "When Played: Return 3 friendly resources to their
# owner's hands. Then, you may resource up to 3 cards from your hand."
# This is the worst realistic pacing case short of a board wipe, and the reason no server-side pacing
# work was needed: the client plays queued slides in PARALLEL with a 60ms stagger and blocks for the
# LONGEST, not the sum.
#
# What to look at:
#   • THREE resources fly up to the hand together, visibly staggered rather than simultaneous.
#   • Total hold is well under a second — it must NOT feel like three sequential 420ms animations.
#   • Then three cards fly back down into the resource row on the second prompt.

## GIVEN
CommonSetup: yyw/yyw/{myResources:6;myhandCardIds:IC27_167}
P1OnlyActions: true
WithP1Hand: [SOR_095 SOR_046 SOR_237]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0&myResources-1&myResources-2
- P1>AnswerDecision:myHand-0&myHand-1&myHand-2

## EXPECT
P1RESCOUNT:6
