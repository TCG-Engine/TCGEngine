# SEC_112 Orn Free Taa (Ground, 0/4) — When Played: search the top 10 of your deck for a Law card,
#   reveal it, and draw it. The deck has one Law card (SEC_126) among event fillers; P1 draws it.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SEC_112
WithP1Deck: SEC_126
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP1Deck: SOR_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SEC_126

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
