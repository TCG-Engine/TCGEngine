# SOR_099 Bright Hope — the return is optional ("You may"). Declining means no unit is
# returned and NO card is drawn. The friendly ground unit stays, hand holds only what's left
# after playing Bright Hope (0), and the deck is untouched.

## GIVEN
CommonSetup: ggw/ggw/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_099
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:0
P1DECKCOUNT:2
