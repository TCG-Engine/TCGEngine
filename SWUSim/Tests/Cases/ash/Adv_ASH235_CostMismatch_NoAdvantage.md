# ASH_235 Sense Through the Force — the Advantage rider only fires when the drawn card's cost equals the
# chosen number. P1 chooses 5 but draws SOR_046 (cost 4 — no match), so no Advantage is given (the card is
# still drawn).
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_235}
WithP1GroundArena: SOR_049:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:5
- P1>AnswerDecision:SOR_046
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1HANDCOUNT:1
