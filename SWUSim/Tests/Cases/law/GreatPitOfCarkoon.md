# EpicSearchSarlacc
#// LAW_023 Great Pit of Carkoon (Base, Command) — "Epic Action [discard a unit from your hand]: Search
#// your deck for a card named The Sarlacc of Carkoon, reveal it, and draw it." P1 discards SEC_080 (cost)
#// and draws LAW_163 (The Sarlacc of Carkoon) from the deck.

## GIVEN
CommonSetup: gbw/grw/{
  myBase:LAW_023
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_080
WithP1Deck: LAW_163

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:LAW_163

## EXPECT
P1DECKCOUNT:0
P1DISCARDCOUNT:1
P1HANDCOUNT:1

---

# DiscardCostOffersOnlyUnitsFromHand
#// LAW_023's Epic cost is "[discard a UNIT from your hand]" — the discard prompt offers exactly the unit
#// cards. Hand: SOR_043 Superlaser Blast (event), SOR_164 Wampa (unit), SOR_077 Takedown (event),
#// SEC_080 (unit) → only the two units are selectable. The choice is left pending so the offer itself is
#// what's asserted.

## GIVEN
CommonSetup: gbw/grw/{myBase:LAW_023}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_043 SOR_164 SOR_077 SEC_080]
WithP1Deck: [LAW_163 SOR_232]

## WHEN
- P1>UseBaseAbility

## EXPECT
P1DECISIONTOOLTIP:Discard_a_unit_from_your_hand_(cost)
P1SELECTABLEEXACT:myHand-1&myHand-3

---

# EpicUnavailableWithNoUnitInHand
#// The Epic cost is unpayable with no UNIT in hand (both cards here are events), so the Action is not
#// usable — and, critically, the once-per-game Epic slot must SURVIVE. Nothing is discarded, the deck is
#// untouched, and the base can still use its Epic later.

## GIVEN
CommonSetup: gbw/grw/{myBase:LAW_023}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_043 SOR_077]
WithP1Deck: [LAW_163 SOR_232]

## WHEN
- P1>UseBaseAbility

## EXPECT
P1BASE:EPICAVAILABLE
P1HANDCOUNT:2
P1DECKCOUNT:2
P1DISCARDCOUNT:0

---

# EpicUnavailableWithEmptyHand
#// Same rule with an empty hand rather than a wrong-type hand: no cost to pay, no Action, and the Epic
#// slot is preserved.

## GIVEN
CommonSetup: gbw/grw/{myBase:LAW_023}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [LAW_163 SOR_232]

## WHEN
- P1>UseBaseAbility

## EXPECT
P1BASE:EPICAVAILABLE
P1HANDCOUNT:0
P1DECKCOUNT:2
P1DISCARDCOUNT:0

---

# UsableWithNoSarlaccInDeck
#// The Epic is a legal Action even when the search can find nothing: the deck holds no LAW_163, so the
#// search prompt comes up with zero selectable cards and the player takes nothing. The cost is still
#// paid (SOR_164 Wampa discarded), the deck is unchanged, and the Epic IS spent — unlike the unpayable-
#// cost cases above, this one really did resolve.

## GIVEN
CommonSetup: gbw/grw/{myBase:LAW_023}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_164 SOR_043]
WithP1Deck: [SOR_232 SOR_083 SOR_077]

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:3
P1DISCARDCOUNT:1
P1BASE:EPICUSED

---

# UsableWithEmptyDeck
#// With an EMPTY deck there is nothing to search at all, and the Epic is still usable: the discard cost
#// resolves normally and the Epic is spent. Guards the deckSize>0 branch — skipping the search must not
#// skip the cost.

## GIVEN
CommonSetup: gbw/grw/{myBase:LAW_023}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_164 SOR_043]

## WHEN
- P1>UseBaseAbility

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
P1DISCARDCOUNT:1
P1BASE:EPICUSED

---

# CostOffersOnlyYourOwnHandUnits
#// COVERAGE: control=CostOffersOnlyYourOwnHandUnits + SarlaccInOpponentDeckIsUnreachable +
#//           SearchesYourOwnDeckNotOpponents — a BASE can never change controller, so owner ≠ controller
#//           is genuinely not constructible for LAW_023; the axis it CAN carry is the seat scope of
#//           "your hand" / "your deck", and all three sections stock P2's hand with discardable units and
#//           P2's deck with copies of The Sarlacc so a wrong-seat read is directly visible ·
#//           offer=DiscardCostOffersOnlyUnitsFromHand + this section · decline=N/A (the Epic has no "you
#//           may" clause; UsableWithNoSarlaccInDeck is the take-nothing analogue) ·
#//           reqboundary=N/A (cost answer then search answer, no state re-read between them).
#//
#// LAW_023 Great Pit of Carkoon — the Epic cost "[discard a unit from YOUR hand]" must see only P1's
#// hand. P1 holds SOR_164 and SEC_080 (units) plus SOR_043 (event); P2 holds SOR_095, SOR_232 and even
#// LAW_163 itself, all discardable-looking units. Exactly P1's two units are selectable — five entries
#// would mean the opponent's hand leaked in. Two P1 candidates also stop the cost auto-resolving.

## GIVEN
CommonSetup: gbw/grw/{myBase:LAW_023}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_164 SEC_080 SOR_043]
WithP2Hand: [SOR_095 SOR_232 LAW_163]
WithP1Deck: [SOR_232 SOR_077]
WithP2Deck: [LAW_163 LAW_163]

## WHEN
- P1>UseBaseAbility

## EXPECT
P1DECISIONTOOLTIP:Discard_a_unit_from_your_hand_(cost)
P1SELECTABLEEXACT:myHand-0&myHand-1

---

# SarlaccInOpponentDeckIsUnreachable
#// LAW_023 Great Pit of Carkoon — "Search YOUR deck for a card named The Sarlacc of Carkoon." P1's deck
#// holds no LAW_163; P2's deck holds TWO. The search must come up empty: P1 pays the cost (SOR_164
#// discarded, hand 3 → 2), draws nothing, and P1's deck stays at two while P2's deck stays at two and
#// P2's hand at three. The Epic is genuinely spent — it resolved, it just found nothing on P1's side.

## GIVEN
CommonSetup: gbw/grw/{myBase:LAW_023}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_164 SEC_080 SOR_043]
WithP2Hand: [SOR_095 SOR_232 LAW_163]
WithP1Deck: [SOR_232 SOR_077]
WithP2Deck: [LAW_163 LAW_163]

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:2
P1DISCARDCOUNT:1
P1DECKCOUNT:2
P2DECKCOUNT:2
P2HANDCOUNT:3
P2DISCARDCOUNT:0
P1BASE:EPICUSED

---

# SearchesYourOwnDeckNotOpponents
#// LAW_023 Great Pit of Carkoon — the positive control that keeps the section above from passing on a
#// search that never ran. Now BOTH decks hold The Sarlacc, so the two seats are only distinguishable by
#// which deck shrinks: P1 discards SOR_164 for the cost and draws LAW_163 out of P1's deck (2 → 1, hand
#// back to 3), while P2's deck is still two cards and P2's hand still three.

## GIVEN
CommonSetup: gbw/grw/{myBase:LAW_023}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_164 SEC_080 SOR_043]
WithP2Hand: [SOR_095 SOR_232 LAW_163]
WithP1Deck: [LAW_163 SOR_232]
WithP2Deck: [LAW_163 LAW_163]

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:LAW_163

## EXPECT
P1HANDCOUNT:3
P1DECKCOUNT:1
P1DISCARDCOUNT:1
P2DECKCOUNT:2
P2HANDCOUNT:3
P1BASE:EPICUSED
