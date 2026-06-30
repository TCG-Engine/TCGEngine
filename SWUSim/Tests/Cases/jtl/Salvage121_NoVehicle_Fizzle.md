# JTL_121 Salvage — with no Vehicle unit in the discard pile, the event fizzles cleanly (nothing
# enters play). The discard holds only a non-Vehicle unit (SOR_095) plus the event itself.

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:JTL_121;discardCardIds:SOR_095}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:0
P1NODECISION
P1DISCARDCOUNT:2
