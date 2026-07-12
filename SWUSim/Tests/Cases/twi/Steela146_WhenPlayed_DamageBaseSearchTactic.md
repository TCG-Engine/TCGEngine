# TWI_146 Steela Gerrera (Unit 4/3, Ground, cost 4, Aggression/Heroism, Fringe) — "When Played/When
# Defeated: You may deal 2 damage to your base. If you do, search the top 8 cards of your deck for a
# Tactic card, reveal it, and draw it." Taking the option deals 2 to P1's own base and draws the Tactic
# (TWI_099 Synchronized Strike) off the top 8; the 3 non-Tactic cards go to the bottom. Base r + leader
# rw cover both Aggression/Heroism pips.

## GIVEN
CommonSetup: rrw/bbw/{myResources:4;handCardIds:TWI_146}
P1OnlyActions: true
WithP1Deck: [TWI_099 SOR_095 SOR_128 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:TWI_099

## EXPECT
P1BASEDMG:2
P1HANDCOUNT:1
P1DECKCOUNT:3
