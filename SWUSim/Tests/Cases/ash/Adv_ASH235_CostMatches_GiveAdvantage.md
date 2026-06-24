# ASH_235 Sense Through the Force (Event, cost 2) — Choose a number, search the top 5 for a card, draw it;
# if its cost is the chosen number, you may give 3 Advantage to a Force unit. P1 chooses 4, draws SOR_046
# (cost 4 — a match), and gives 3 Advantage to the Force unit SOR_049.
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
- P1>AnswerDecision:4
- P1>AnswerDecision:SOR_046
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:3
P1HANDCOUNT:1
