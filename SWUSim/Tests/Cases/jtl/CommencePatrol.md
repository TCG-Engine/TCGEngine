# DiscardToDeckBottom_XWing
#// JTL_205 — Put another card in a discard pile on the bottom of its owner's deck. If you do, create an
#// X-Wing token. P1 picks SOR_095 from P2's discard → it goes to the bottom of P2's deck → P1 gets an
#// X-Wing token in the space arena.

## GIVEN
CommonSetup: byw/byk/{myResources:5;handCardIds:JTL_205;theirDiscardCardIds:SOR_095}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirDiscard-0

## EXPECT
P2DISCARDCOUNT:0
P2DECKCOUNT:1
P1SPACEARENACOUNT:1

---

# FriendlyDiscardToDeckBottom_XWing
#// JTL_205 Commence Patrol — the returned card can be a FRIENDLY discard too. P1 has SOR_095 in its own
#// discard; playing Commence Patrol puts it on the bottom of P1's deck and creates an X-Wing token.

## GIVEN
CommonSetup: byw/byk/{myResources:5;handCardIds:JTL_205;myDiscardCardIds:SOR_095}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1DECKCOUNT:1
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_T02

---

# NoDiscardCards_NoXWing
#// JTL_205 Commence Patrol — with no card in either discard pile to return, the effect does nothing: no
#// X-Wing token is created and Commence Patrol just goes to the discard (played anyway).

## GIVEN
CommonSetup: byw/byk/{myResources:5;handCardIds:JTL_205}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS

## EXPECT
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:1
