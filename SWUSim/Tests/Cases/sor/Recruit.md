# SearchUnitDraw
#// SOR_123 Recruit (Event, cost 1) — Search the top 5 of your deck for a unit, reveal it, and
#// draw it (rest to the bottom). The top 5 contain one unit (Battlefield Marine SOR_095) among
#// non-unit (event) fillers; the player picks it and draws it. Recruit itself goes to discard.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SOR_123
WithP1Deck: SOR_095
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:7
P1DISCARDCOUNT:1
