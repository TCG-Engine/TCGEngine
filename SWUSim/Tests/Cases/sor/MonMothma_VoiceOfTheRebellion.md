# WhenPlayed_SearchRebelDraw
#// SOR_096 Mon Mothma (1/3, Ground) — When Played: Search the top 5 of your deck for a REBEL
#// card, reveal it, and draw it (rest to the bottom). The top 5 contain one Rebel (Battlefield
#// Marine SOR_095) among non-Rebel fillers; the player picks it and draws it.

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_096
WithP1Deck: SOR_095
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:7
P1GROUNDARENACOUNT:1
