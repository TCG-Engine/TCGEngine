# ChooseOpponent_Draws2
#// SOR_171 Mission Briefing (Event, cost 3) — Choose a player. They draw 2 cards. P1 plays it
#// and (via the option-picker) chooses the opponent, so P2 draws 2 (P2 hand 0 → 2, deck −2).

## GIVEN
CommonSetup: ggw/ggw/{myResources:5}
P1OnlyActions: true
WithP1Hand: SOR_171
WithP2Deck: SOR_128
WithP2Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P2HANDCOUNT:2
P2DECKCOUNT:0
