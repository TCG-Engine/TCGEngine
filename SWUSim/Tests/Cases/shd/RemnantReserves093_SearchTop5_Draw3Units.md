# SHD_093 Remnant Reserves (4-cost Command/Villainy event) — "Search the top 5 cards of your deck for
# up to 3 units, reveal them, and draw them." Top 5 = 3 units (SOR_046, SOR_095, SOR_164) + 2 events
# (SOR_171). P1 picks all 3 units → drawn to hand; the 2 events go to the bottom. Hand +3, deck -3.

## GIVEN
CommonSetup: ggk/ggk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SHD_093
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_164
WithP1Deck: SOR_171
WithP1Deck: SOR_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_046,SOR_095,SOR_164

## EXPECT
P1HANDCOUNT:3
P1DECKCOUNT:2
