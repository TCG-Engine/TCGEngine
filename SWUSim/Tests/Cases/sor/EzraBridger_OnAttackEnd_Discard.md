# SOR_192 Ezra Bridger — On Attack End: choosing "Discard" puts the top card into the discard pile
# (From DECK). Ezra attacks P2's base for 3; the top card SOR_095 is milled (deck 3 → 2, discard
# 0 → 1).

## GIVEN
CommonSetup: rrw/rrw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: SOR_192:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Discard

## EXPECT
P2BASEDMG:3
P1DECKCOUNT:2
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_095
P1DISCARDUNIT:0:FROM:DECK
P1GROUNDARENACOUNT:1
