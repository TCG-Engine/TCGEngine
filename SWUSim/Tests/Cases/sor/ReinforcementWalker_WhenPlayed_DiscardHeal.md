# SOR_119 Reinforcement Walker — When Played: look at the top card; choosing "Discard and heal 3"
# discards the top card (to discard, From DECK) and heals 3 damage from P1's base. P1's base starts
# at 5 damage → heals to 2. Top card SOR_095 is milled (deck 3 → 2, discard 0 → 1). Nothing drawn.

## GIVEN
CommonSetup: ggw/ggw/{myResources:8;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: SOR_119
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Discard

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:0
P1DECKCOUNT:2
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_095
P1DISCARDUNIT:0:FROM:DECK
P1BASEDMG:2
