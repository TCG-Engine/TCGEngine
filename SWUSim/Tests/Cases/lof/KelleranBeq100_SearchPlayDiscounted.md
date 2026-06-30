# LOF_100 Kelleran Beq — When Played: search the top 7 for a unit, reveal it, and play it costing 3 less.
# The deck is all SOR_095 (cost 3 → 0 after −3), so P1 plays one for free; Kelleran + the searched unit
# are both in play, and 6 cards remain in the deck.

## GIVEN
CommonSetup: ggw/rrk/{myResources:7;handCardIds:LOF_100}
P1OnlyActions: true
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1DECKCOUNT:6
