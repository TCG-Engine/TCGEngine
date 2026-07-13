# Twin Suns Phase 3 / Group B: SOR_171 Mission Briefing "Choose a player. They draw 2 cards." In a
# 3-player game the "You&Opponent" binary picker expands to You / P2 / P3. Choosing P3 makes P3 draw 2
# (proving the picker reaches a specific opponent, not just the lone/first one). P3 starts with an empty
# hand and a seeded deck.

## GIVEN
CommonSetup: rrw/ggk
WithSeatOrder: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: SOR_171
WithP1Resources: 3
WithP3Deck: SOR_095
WithP3Deck: SOR_095
WithP3Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P3

## EXPECT
SEATCOUNT:3
P3HANDCOUNT:2
P3DECKCOUNT:1
