# TS26_039 Captain Vaughn (Unit 2/5, cost 3) — Grit + When Defeated: search the top 3 cards of your deck
# for a card and draw it; then put a card from your hand on top of your deck. Vaughn (pre-damaged) attacks
# LAW_124 and dies; it draws SOR_095 from the top 3, then puts SEC_080 (from hand) on top of the deck.
## GIVEN
CommonSetup: bbw/rrk/{handCardIds:SEC_080}
WithP1GroundArena: TS26_039:1:1
WithP2GroundArena: LAW_124:1:0
WithP1Deck: [SOR_095 SOR_046 SOR_128]
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:SOR_095
- P1>AnswerDecision:myHand-0
## EXPECT
P1DECKTOPCARD:SEC_080
P1HANDCOUNT:1
