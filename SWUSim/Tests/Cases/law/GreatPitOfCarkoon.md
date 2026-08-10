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
