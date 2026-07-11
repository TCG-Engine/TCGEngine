# SHD_044 Razor Crest (4-cost 3/4 space, Vigilance/Heroism) — Restore 2 + "When Played: You may return an
# upgrade from your discard pile to your hand." An upgrade (SOR_120 Academy Training) sits in P1's discard;
# on play, the MZMAYCHOOSE offers it and P1 takes it → it moves discard → hand.

## GIVEN
CommonSetup: bbw/bbw/{myResources:4;discardCardIds:SOR_120}
P1OnlyActions: true
WithP1Hand: SHD_044

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SHD_044
P1HANDCOUNT:1
P1DISCARDCOUNT:0
