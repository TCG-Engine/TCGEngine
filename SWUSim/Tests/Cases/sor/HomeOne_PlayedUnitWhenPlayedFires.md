# SOR_102 Home One — a unit played from discard runs its OWN When Played (nested trigger). SOR_096
# Daring Raid (Command/Heroism, cost 2 → free after -3, "When Played: search top 5 for a Rebel card
# and draw it") is played from discard; its nested search finds the Rebel SOR_095 in P1's deck and
# draws it (deck → hand), proving the played unit's entry trigger resolves.

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;discardCardIds:SOR_096}
P1OnlyActions: true
WithP1Deck: SOR_095
WithP1Hand: SOR_102

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
P1DECKCOUNT:0
