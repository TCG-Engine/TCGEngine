# SHD_044 Razor Crest — the return is a "may": declining (AnswerDecision:-) leaves the upgrade in the
# discard pile and nothing in hand.

## GIVEN
CommonSetup: bbw/bbw/{myResources:4;discardCardIds:SOR_120}
P1OnlyActions: true
WithP1Hand: SHD_044

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SHD_044
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_120
