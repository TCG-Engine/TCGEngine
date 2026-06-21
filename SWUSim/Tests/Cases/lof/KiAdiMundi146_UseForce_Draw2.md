# LOF_146 Ki-Adi-Mundi (4/4) — When Played: may use the Force → draw 2 cards.

## GIVEN
CommonSetup: rrw/rrk/{myResources:4;handCardIds:LOF_146}
P1OnlyActions: true
WithP1Force: true
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1NOFORCE
P1HANDCOUNT:2
