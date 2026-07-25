# SharedTraitSearch
#// LOF_219 Psychometry — Choose another card in your discard; search the top 5 for a card sharing a trait
#// with it, reveal+draw. Discard has SOR_046 (Rebel,Trooper); the deck's SOR_146 (Rebel,Spectre) shares
#// Rebel and is drawn.

## GIVEN
CommonSetup: yyw/ggk/{myResources:1;handCardIds:LOF_219;discardCardIds:SOR_046}
P1OnlyActions: true
WithP1Deck: SOR_146

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_146

## EXPECT
P1HANDCOUNT:1

---

# OnlyCardInDiscard_DoNothing
#// LOF_219 Psychometry — needs "another card in your discard pile". With an empty discard, after playing,
#// Psychometry is the only card there, so there is nothing to choose and no search happens: it just goes to
#// discard, the deck is untouched. Ref: "do nothing if it is the only card in discard".

## GIVEN
CommonSetup: yyw/ggk/{myResources:1;handCardIds:LOF_219}
P1OnlyActions: true
WithP1Deck: SOR_146

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1DISCARDCOUNT:1
P1HANDCOUNT:0
P1DECKCOUNT:1

---

# EmptyDeck_DoNothing
#// LOF_219 Psychometry — with a card to choose (SOR_046) but an empty deck, the top-5 search finds nothing to
#// reveal/draw: no card is drawn. Ref: "do nothing when there is an empty deck".

## GIVEN
CommonSetup: yyw/ggk/{myResources:1;handCardIds:LOF_219;discardCardIds:SOR_046}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1DISCARDCOUNT:2
P1HANDCOUNT:0

---

# TakeNothing_NoDraw
#// LOF_219 Psychometry — the search draw is optional. After choosing SOR_046 (Rebel,Trooper) as the
#// trait-anchor card, P1 declines to take a card: nothing is drawn and the trait-sharing SOR_146 stays in the deck.

## GIVEN
CommonSetup: yyw/ggk/{myResources:1;handCardIds:LOF_219;discardCardIds:SOR_046}
P1OnlyActions: true
WithP1Deck: SOR_146

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:1

---

# TraitFilter_DrawSharingLeaveOther
#// LOF_219 Psychometry — only a card that shares a trait with the chosen card can be drawn. Reference card is
#// SOR_046 (Rebel,Trooper); the deck top 5 has SOR_180 (Imperial,Vehicle,Fighter — no shared trait) and
#// SOR_146 (Rebel,Spectre — shares Rebel). P1 draws SOR_146; the non-sharing SOR_180 is put back in the deck.

## GIVEN
CommonSetup: yyw/ggk/{myResources:1;handCardIds:LOF_219;discardCardIds:SOR_046}
P1OnlyActions: true
WithP1Deck: SOR_180
WithP1Deck: SOR_146

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_146

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_146
P1DECKCOUNT:1
P1DECKTOPCARD:SOR_180
