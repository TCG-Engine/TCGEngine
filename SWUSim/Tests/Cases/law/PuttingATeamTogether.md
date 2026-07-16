# SearchDrawUnit
#// LAW_166 Putting a Team Together (Command event, cost 1) — "Search the top 8 cards of your deck for a
#// Vigilance, Aggression, or Cunning unit, reveal it, and draw it." SOR_046 (Vigilance unit) is the only
#// match among the top cards (SOR_237 is Heroism-only) -> drawn.

## GIVEN
CommonSetup: ggw/bgw/{myResources:1}
WithP1Deck: SOR_046
WithP1Deck: SOR_237
WithP1Deck: SOR_237
WithP1Hand: LAW_166

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_046

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:2
