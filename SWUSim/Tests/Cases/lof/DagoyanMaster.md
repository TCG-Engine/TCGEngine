# UseForce_SearchForceUnit
#// LOF_115 Dagoyan Master (5/5) — When Played: may use the Force → search the top 5 for a Force unit,
#// reveal and draw it. P1 plays it with the Force, uses it, and draws the lone Force unit (LOF_050) from
#// the top 5; the 4 non-Force cards go to the bottom.

## GIVEN
CommonSetup: ggw/rrk/{myResources:5;handCardIds:LOF_115}
P1OnlyActions: true
WithP1Force: true
WithP1Deck: LOF_050
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:LOF_050

## EXPECT
P1NOFORCE
P1HANDCOUNT:1
P1GROUNDARENACOUNT:1
