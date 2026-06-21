# SEC_211 Faith in Your Friends (Event, cost 2, Cunning/Heroism) — "Search the top 3 cards of your
#   deck for a card and draw it. Then, you may disclose CunningCunningCunningHeroismHeroism → create 2
#   Spy tokens." Deck = 3 SOR_128; draw one. Then disclose three SEC_211 (Cunning,Heroism each →
#   3 Cunning + 3 Heroism cover CCCHH) → 2 Spy tokens.

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
- P1>AnswerDecision:myHand-0&myHand-1&myHand-2

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SEC_T01
P1HANDCOUNT:4
P1DECKCOUNT:2
P1NODECISION
