# SOR_246 You're My Only Hope — decline: "you MAY play it". P1 looks at the top card (SOR_049
# Obi-Wan) and chooses Leave → nothing played, the card stays on top. P1 still paid 3 for the event
# (→ 0), and the event is in the discard.

## GIVEN
CommonSetup: byw/byw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_246
WithP1Deck: SOR_049
WithP1Deck: SOR_189
WithP1Deck: SOR_189

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Leave

## EXPECT
P1GROUNDARENACOUNT:0
P1DECKCOUNT:3
P1DECKTOPCARD:SOR_049
P1RESAVAILABLE:0
P1DISCARDCOUNT:1
