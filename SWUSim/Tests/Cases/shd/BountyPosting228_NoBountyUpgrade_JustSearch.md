# SHD_228 Bounty Posting — deck has no Bounty upgrade → the search finds nothing (blank pick), nothing is
# drawn or played, and no play offer appears. Only the SHD_228 event sits in discard.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_228
WithP1Deck: [SOR_095 SEC_080 SOR_128 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:

## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1NODECISION
