# SOR_107 Command — Resource (put this event into play as a resource) + Return (return a unit from your
# discard to hand). After playing SOR_107 the discard holds [SEC_080, SOR_107]; Resource moves SOR_107
# to the resource row (count 6→7), Return moves SEC_080 to hand. Discard ends empty.

## GIVEN
CommonSetup: ggw/rrk/{myResources:6;handCardIds:SOR_107;discardCardIds:SEC_080}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Resource
- P1>AnswerDecision:Return

## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:0
P1RESCOUNT:7
