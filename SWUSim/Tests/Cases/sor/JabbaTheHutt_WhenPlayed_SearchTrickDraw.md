# SOR_181 Jabba the Hutt (Unit 2/8, cost 4, Cunning/Villainy) — "When Played: Search the top 8 cards
# of your deck for a TRICK event, reveal it, and draw it." Deck holds a non-Trick event (SOR_171), a
# non-Trick unit (SOR_095), and one Trick event (SOR_222). Only the Trick event is offered (filter is
# Trick trait + event) → drawn. The other two go to the bottom (deck 3 → 2).

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
P1OnlyActions: true
WithP1Deck: SOR_171
WithP1Deck: SOR_095
WithP1Deck: SOR_222
WithP1Hand: SOR_181

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_222

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
P1HANDCARD:0:SOR_222
P1DECKCOUNT:2
