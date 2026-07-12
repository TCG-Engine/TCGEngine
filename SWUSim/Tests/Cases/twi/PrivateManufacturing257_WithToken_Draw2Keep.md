# TWI_257 Private Manufacturing — controlling a token unit (TWI_T01) skips the put-back, so both drawn
# cards are kept and no decision is pending.

## GIVEN
CommonSetup: rrk/bbw/{myResources:2;handCardIds:TWI_257}
P1OnlyActions: true
WithP1GroundArena: TWI_T01:1:0
WithP1Deck: [SOR_046 SOR_046 SOR_095 SOR_128]

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1HANDCOUNT:2
P1DECKCOUNT:2
