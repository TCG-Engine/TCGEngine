# SEC_186 Garindan (Ground, 1/3, cost 2) — When Played: name a card; look at an opponent's hand and
#   discard a card with that name from it. P1 names Battlefield Marine; P2's SOR_095 is discarded.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
P1OnlyActions: true
WithP2Hand: SOR_095
WithP1Hand: SEC_186

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Battlefield Marine

## EXPECT
P2HANDCOUNT:0
P1GROUNDARENACOUNT:1
