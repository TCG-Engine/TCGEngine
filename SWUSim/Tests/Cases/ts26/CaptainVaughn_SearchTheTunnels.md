# WhenDefeatedSearchDrawThenTop
#// TS26_39 Captain Vaughn (Unit 2/5, cost 3) — Grit + When Defeated: search the top 3 cards of your deck
#// for a card and draw it; then put a card from your hand on top of your deck. Vaughn (pre-damaged) attacks
#// LAW_124 and dies; it draws SOR_095 from the top 3, then puts SEC_080 (from hand) on top of the deck.
## GIVEN
CommonSetup: bbw/rrk/{handCardIds:SEC_080}
WithP1GroundArena: TS26_39:1:1
WithP2GroundArena: LAW_124:1:0
WithP1Deck: [SOR_095 SOR_046 SOR_128]
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:SOR_095
- P1>AnswerDecision:myHand-0
## EXPECT
P1DECKTOPCARD:SEC_080
P1HANDCOUNT:1

---

# DecliningTheDrawStillPutsACardOnTop
#// TS26_39 Captain Vaughn — the draw is optional, the top-of-deck step is not. Declining the search leaves
#// the three cards where they were, then SEC_080 goes from hand onto the deck: hand 0, deck back up to 4.

## GIVEN
CommonSetup: bbw/rrk/{handCardIds:SEC_080}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TS26_39:1:1
WithP2GroundArena: LAW_124:1:0
WithP1Deck: [SOR_095 SOR_046 SOR_128]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-
- P1>AnswerDecision:myHand-0

## EXPECT
P1DECKTOPCARD:SEC_080
P1HANDCOUNT:0
P1DECKCOUNT:4

---

# FewerThanThreeCardsInDeckIsFine
#// TS26_39 Captain Vaughn — "search the top 3" with only 2 cards there searches what exists. SOR_046 is
#// drawn and SEC_080 goes on top: hand back to 1, deck 2.

## GIVEN
CommonSetup: bbw/rrk/{handCardIds:SEC_080}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TS26_39:1:1
WithP2GroundArena: LAW_124:1:0
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:SOR_046
- P1>AnswerDecision:myHand-0

## EXPECT
P1DECKTOPCARD:SEC_080
P1HANDCOUNT:1
P1DECKCOUNT:2

---

# AnEMPTYDeckStillGetsTheCardFromHand
#// TS26_39 Captain Vaughn — "Search … and draw it. THEN, put a card from your hand on top of your deck."
#// The clauses are joined by "Then", not "If you do", so an empty deck (nothing to search, nothing to
#// draw) must NOT swallow the second half: SEC_080 leaves hand and becomes the deck's only card.
#// Discriminating: the search helper no-ops on an empty deck and never reached the continuation, so the
#// whole When Defeated used to fizzle and the card stayed in hand.

## GIVEN
CommonSetup: bbw/rrk/{handCardIds:SEC_080}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TS26_39:1:1
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1DECKTOPCARD:SEC_080
P1HANDCOUNT:0
P1DECKCOUNT:1
