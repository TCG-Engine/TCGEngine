# TS26_080 Reveal Intentions — edge: the opponent's hand is empty, so the caster (P1) discards nothing
# from it (no decision), but P2 still discards a card from P1's hand, and BOTH players still draw a card.
## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
WithActivePlayer: 1
WithP1Hand: TS26_080
WithP1Hand: SOR_095
WithP1Hand: SOR_046
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:theirHand-0
## EXPECT
P2DISCARDCOUNT:0
P1DISCARDCOUNT:2
P1DECKCOUNT:1
P2DECKCOUNT:1
P1HANDCOUNT:2
P2HANDCOUNT:1
