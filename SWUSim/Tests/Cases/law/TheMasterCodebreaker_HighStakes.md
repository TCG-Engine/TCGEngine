# FirstGambitDiscount
#// LAW_229 The Master Codebreaker — "the first Gambit card you play each round costs 1 resource less."
#// With LAW_229 in play, SEC_211 (Gambit, Cunning/Heroism, cost 2) plays for 1 (off only by the discount):
#// with just 1 ready resource it leaves hand for discard and ends at 0 ready (empty deck -> search fizzles).
#// COVERAGE: offer=SearchWindowIncludesEighthCard + SearchWindowExcludesNinthCard (the top-deck search
#//           pool is asserted behaviorally: the in-window Gambit is takeable, the out-of-window one
#//           resolves to nothing even when named; the search prompt is not an MZ pool, so no
#//           SELECTABLEEXACT applies) · reqboundary=SearchGambit (the search is answered on a later
#//           request after the play) · control=N/A (no control-change interaction; the discount is a
#//           static friendly aura) · boundary=SearchWindowIncludesEighthCard vs
#//           SearchWindowExcludesNinthCard (position 8 vs 9); FirstGambitDiscount vs
#//           SecondGambitNotDiscounted (first vs second Gambit); SearchEmptyDeckDoesNothing ·
#//           decline=SearchTakeNothingNoGambit (take nothing).

## GIVEN
CommonSetup: yyw/bgw/{myResources:1}
WithP1GroundArena: LAW_229:1:0
WithP1Hand: SEC_211

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1RESAVAILABLE:0
P1DISCARDCOUNT:1

---

# SearchGambit
#// LAW_229 The Master Codebreaker (Cunning, cost 2) — When Played: search the top 8 cards for a Gambit
#// card, reveal it, and draw it. SOR_223 (Gambit) is the match; SOR_237 is left.

## GIVEN
CommonSetup: yyk/bgw/{myResources:2}
WithP1Deck: SOR_223
WithP1Deck: SOR_237
WithP1Hand: LAW_229

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_223

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:1

---

# SearchTakeNothingNoGambit
#// LAW_229 The Master Codebreaker — When Played search of the top 8 finds no Gambit card (deck is SOR_095
#// and SOR_237, neither a Gambit), so nothing is selectable and the player takes nothing: no card is drawn
#// and Codebreaker resolves into the ground arena.

## GIVEN
CommonSetup: yyk/bgw/{myResources:2}
WithP1Deck: SOR_095
WithP1Deck: SOR_237
WithP1Hand: LAW_229

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:2
P1GROUNDARENAUNIT:0:CARDID:LAW_229

---

# SearchEmptyDeckDoesNothing
#// LAW_229 The Master Codebreaker — When Played with an empty deck: the search fizzles with no prompt and
#// Codebreaker still resolves into play; nothing is drawn.

## GIVEN
CommonSetup: yyk/bgw/{myResources:2}
WithP1Hand: LAW_229

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:0
P1GROUNDARENAUNIT:0:CARDID:LAW_229

---

# SecondGambitNotDiscounted
#// LAW_229 The Master Codebreaker — only the FIRST Gambit card each round is reduced by 1. With Codebreaker
#// already in play and two Gambit events (SEC_211, cost 2) in hand, the first plays for 1 and the second
#// for the full 2 -> 3 resources spent total; both events go to discard (empty deck fizzles their search).

## GIVEN
CommonSetup: yyw/bgw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: LAW_229:1:0
WithP1Hand: [SEC_211 SEC_211]

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1RESAVAILABLE:0
P1DISCARDCOUNT:2

---

# NonGambitNotReduced
#// LAW_229 The Master Codebreaker — the discount applies only to Gambit cards. With Codebreaker in play,
#// a non-Gambit unit (SOR_095 Battlefield Marine, cost 2) still costs the full 2, leaving 0 ready.

## GIVEN
CommonSetup: grw/bgw/{myResources:2}
WithP1GroundArena: LAW_229:1:0
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENACOUNT:2

---

# SearchWindowIncludesEighthCard
#// LAW_229 The Master Codebreaker — the When Played search window is exactly the top 8 cards. A Gambit
#// card (SOR_223) sitting at position 8 (the last card inside the window) is found and drawn; the deck
#// keeps the other 8 cards.

## GIVEN
CommonSetup: yyk/bgw/{myResources:2}
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_223 SOR_095]
WithP1Hand: LAW_229

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_223

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_223
P1DECKCOUNT:8
P1GROUNDARENAUNIT:0:CARDID:LAW_229

---

# SearchWindowExcludesNinthCard
#// LAW_229 The Master Codebreaker — the boundary pair to the section above: when the ONLY Gambit card
#// (SOR_223) sits at position 9, one past the 8-card window, it cannot be taken. The search decision is
#// answered with SOR_223 by name, but since that card is outside the peeked window the answer resolves
#// to taking nothing: no card is drawn and the deck keeps all 9 cards, the never-peeked SOR_223 on top
#// (the 8 peeked cards go to the bottom).

## GIVEN
CommonSetup: yyk/bgw/{myResources:2}
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_223]
WithP1Hand: LAW_229

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_223

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:9
P1DECKTOPCARD:SOR_223
P1GROUNDARENAUNIT:0:CARDID:LAW_229

---

# ForeignOwnedCodebreaker_SearchesItsControllersDeck
#// LAW_229 — control axis, clause 2. "Search the top 8 cards of YOUR deck" resolves from the ability's
#// CONTROLLER. LAW_229 is owned by P2 (it is the top card of P2's deck) but P1 plays it for free via
#// LAW_215 Vermillion, so it enters play under P1 and its When Played must search P1's deck.
#// Both decks hold a DIFFERENT Gambit card, so the searched deck is readable from the end state:
#//   · P1's deck: SOR_223 Don't Get Cocky (Gambit) + SOR_237 Alliance X-Wing (not a Gambit)
#//   · P2's deck: LAW_229 itself (revealed and played away) + SOR_246 You're My Only Hope (Gambit)
#// Answering SOR_223 would THROW if the owner's deck had been searched, and the counts pin it from
#// the other side: P1 draws SOR_223 and keeps 1 card in deck while P2's deck still holds its untouched
#// SOR_246 and P2's hand is empty. Owner-scoped resolution would have drawn SOR_246 instead.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_215:1:0
WithP1Deck: SOR_223
WithP1Deck: SOR_237
WithP2Deck: LAW_229
WithP2Deck: SOR_246

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Theirs
- P1>AnswerDecision:You
- P1>AnswerDecision:YES
- P1>AnswerDecision:SOR_223

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_229
P1HANDCOUNT:1
P1HANDCARD:0:SOR_223
P1DECKCOUNT:1
P2DECKCOUNT:1
P2HANDCOUNT:0

---

# GambitDiscountFollowsTheController
#// LAW_229 — control axis, clause 1. "The first Gambit card YOU play each round costs 1 resource
#// less" is a static ability whose "you" is the unit's CONTROLLER, so a Codebreaker whose control has
#// changed discounts for the NEW controller. LAW_229 sits in P2's ground arena, CONTROLLED BY P2 but
#// OWNED BY P1. P2 has exactly 1 ready resource and holds SEC_211 Faith in Your Friends (Gambit,
#// Cunning/Heroism, printed cost 2) — on-aspect for P2's yyw leader/base, so no aspect penalty is
#// hiding anything. The play can only go through at 1 resource if the discount reached P2: the event
#// leaves hand for P2's discard and P2 ends at 0 ready.
#// (Both decks are empty, so SEC_211's own top-3 search fizzles and adds no decision.)

## GIVEN
CommonSetup: yyw/yyw/{}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 1
WithP2Hand: SEC_211
WithP2GroundArenaControlled: LAW_229:1

## WHEN
- P2>PlayHand:0

## EXPECT
P2HANDCOUNT:0
P2RESAVAILABLE:0
P2DISCARDCOUNT:1

---

# GambitDiscountDoesNotFollowTheOwner
#// LAW_229 — the mirror that makes GambitDiscountFollowsTheController discriminating. Same split
#// seat: P1 OWNS the Codebreaker but P2 CONTROLS it. P1 holds the same Gambit event (SEC_211, cost 2,
#// on-aspect for P1's yyw base/leader) with exactly 1 ready resource. Because P1 does not control the
#// Codebreaker, no discount applies, the event is unaffordable, and the attempt is a silent no-op:
#// the card stays in hand, the resource stays ready, and nothing reaches the discard.
#// Verified discriminating from both ends before this pair was written: with the same 1 resource and
#// the Codebreaker in P1's OWN arena the play succeeds, and with no Codebreaker on the board at all
#// P2's play fails — so the only variable here is who CONTROLS it.
#//
#// LEDGER CORRECTION: this file's FirstGambitDiscount ledger previously recorded
#// "control=N/A (no control-change interaction; the discount is a static friendly aura)". That is
#// wrong on both clauses — a static friendly aura is exactly what a control change re-seats, and the
#// deck search is owner-scoped text. Both are now covered; the entry is corrected below.
#//
#// COVERAGE: offer=SearchWindowIncludesEighthCard + SearchWindowExcludesNinthCard (the top-deck
#//           search pool is asserted behaviorally: the in-window Gambit is takeable, the out-of-window
#//           one resolves to nothing even when named; the search prompt is not an MZ pool, so no
#//           SELECTABLEEXACT applies) · reqboundary=SearchGambit (the search is answered on a later
#//           request after the play) · control=ForeignOwnedCodebreaker_SearchesItsControllersDeck
#//           ("your deck" follows the controller of a foreign-owned Codebreaker) +
#//           GambitDiscountFollowsTheController + GambitDiscountDoesNotFollowTheOwner (the "first
#//           Gambit card you play" discount re-seats with control, in both directions) ·
#//           boundary=SearchWindowIncludesEighthCard vs SearchWindowExcludesNinthCard (position 8 vs
#//           9); FirstGambitDiscount vs SecondGambitNotDiscounted (first vs second Gambit);
#//           SearchEmptyDeckDoesNothing · decline=SearchTakeNothingNoGambit (take nothing).

## GIVEN
CommonSetup: yyw/yyw/{myResources:1}
P1OnlyActions: true
WithP2GroundArenaControlled: LAW_229:1
WithP1Hand: SEC_211

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1RESAVAILABLE:1
P1DISCARDCOUNT:0
