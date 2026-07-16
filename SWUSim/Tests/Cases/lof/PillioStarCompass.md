# SearchUnit
#// LOF_122 Pillio Star Compass — When Played: search the top 3 for a unit, reveal and draw it. Played onto
#// SOR_095, P1 draws SOR_046 from the top 3.

## GIVEN
CommonSetup: bbw/rrk/{myResources:6;handCardIds:LOF_122}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_046

## EXPECT
P1HANDCOUNT:1
