# SOR_192 Ezra Bridger — On Attack End: choosing "Play" plays the top card from the deck, paying
# its normal cost. Ezra attacks P2's base for 3; the top card is SOR_157 (cost 1, Aggression, no
# entry trigger). With matched Aggression aspects and 1 ready resource, it is played to the ground
# arena (arena 1 → 2, deck 3 → 2, resources 1 → 0).

## GIVEN
CommonSetup: rrw/rrw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_192:1:0
WithP1Deck: SOR_157
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Play

## EXPECT
P2BASEDMG:3
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_157
P1DECKCOUNT:2
P1RESAVAILABLE:0
P1DISCARDCOUNT:0
