# NoTokens_Draw2PutBack2
#// TWI_257 Private Manufacturing (Event, cost 2, Supply) — "Draw 2 cards. If you control no token units,
#// put 2 cards from your hand on the bottom of your deck in any order." With no token units, drawing 2 then
#// putting 2 back leaves the hand empty and the deck size unchanged.

## GIVEN
CommonSetup: rrk/bbw/{myResources:2;handCardIds:TWI_257}
P1OnlyActions: true
WithP1Deck: [SOR_046 SOR_046 SOR_095 SOR_128]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0&myHand-1

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:4

---

# WithToken_Draw2Keep
#// TWI_257 Private Manufacturing — controlling a token unit (TWI_T01) skips the put-back, so both drawn
#// cards are kept and no decision is pending.

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
