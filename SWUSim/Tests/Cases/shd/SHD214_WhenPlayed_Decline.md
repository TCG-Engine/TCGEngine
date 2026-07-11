# SHD_214 — the "you may" is optional. Declining the resource return skips the whole chain: no resource
# returned, no top-card ramp. Resources stay at 4, deck keeps its card, nothing added to hand.

## GIVEN
CommonSetup: yyw/yyw/{myResources:4}
P1OnlyActions: true
WithP1Hand: SHD_214
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1RESCOUNT:4
P1HANDCOUNT:0
P1DECKCOUNT:1
