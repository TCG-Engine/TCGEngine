# SEC_211 Faith in Your Friends — the search still draws, but declining the disclose creates no Spy tokens.

## GIVEN
CommonSetup: yyw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SEC_211
WithP1Hand: SEC_211
WithP1Hand: SEC_211
WithP1Hand: SEC_211
WithP1Deck: [SOR_128 SOR_128 SOR_128]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_128
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:4
P1DECKCOUNT:2
P1NODECISION
