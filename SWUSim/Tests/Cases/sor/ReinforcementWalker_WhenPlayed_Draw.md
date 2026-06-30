# SOR_119 Reinforcement Walker (Unit 6/9, cost 8, Command, Vehicle/Walker) — When Played:
# look at the top card; choosing "Draw" draws it. P1 plays the Walker (matched Command aspects,
# 8 resources → printed cost 8), then via the option picker chooses Draw. Top card (SOR_095) is
# drawn (hand 0 → 1), deck 3 → 2, nothing discarded.

## GIVEN
CommonSetup: ggw/ggw/{myResources:8}
P1OnlyActions: true
WithP1Hand: SOR_119
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Draw

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
P1DECKCOUNT:2
P1DISCARDCOUNT:0
