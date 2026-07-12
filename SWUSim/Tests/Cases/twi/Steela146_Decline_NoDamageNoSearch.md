# TWI_146 Steela Gerrera — declining the "may" (AnswerDecision:NO) deals no base damage and runs no
# search: base stays clean, hand ends empty (only the played Steela left hand), deck unchanged.

## GIVEN
CommonSetup: rrw/bbw/{myResources:4;handCardIds:TWI_146}
P1OnlyActions: true
WithP1Deck: [TWI_099 SOR_095 SOR_128 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1BASEDMG:0
P1HANDCOUNT:0
P1DECKCOUNT:4
