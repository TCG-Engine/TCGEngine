# LOF_219 Psychometry — Choose another card in your discard; search the top 5 for a card sharing a trait
# with it, reveal+draw. Discard has SOR_046 (Rebel,Trooper); the deck's SOR_146 (Rebel,Spectre) shares
# Rebel and is drawn.

## GIVEN
CommonSetup: yyw/ggk/{myResources:1;handCardIds:LOF_219;discardCardIds:SOR_046}
P1OnlyActions: true
WithP1Deck: SOR_146

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_146

## EXPECT
P1HANDCOUNT:1
