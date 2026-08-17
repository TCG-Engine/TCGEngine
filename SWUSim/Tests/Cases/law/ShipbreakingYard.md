# EpicMill3ReturnTop
#// LAW_026 Shipbreaking Yard (Base, Aggression) — "Epic Action: Discard 3 cards from your deck. You may
#// return a card discarded this way to the top of your deck." P1 mills SOR_046/SOR_095/SOR_128 then
#// returns SOR_046 (myDiscard-0) to the top → deck top = SOR_046, deck count 1, discard 2.

## GIVEN
CommonSetup: rbw/grw/{
  myBase:LAW_026
}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_128

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1DECKTOPCARD:SOR_046
P1DECKCOUNT:1
P1DISCARDCOUNT:2
P1BASE:EPICUSED

---

# EpicMillOnly2InDeck
#// LAW_026 Shipbreaking Yard — with only 2 cards in the deck, "discard the top 3" discards all it can
#// (2). P1 mills SOR_046/SOR_095 then returns SOR_046 (myDiscard-0) to the top → deck top = SOR_046,
#// deck count 1, discard 1. Epic consumed.

## GIVEN
CommonSetup: rbw/grw/{
  myBase:LAW_026
}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: SOR_046
WithP1Deck: SOR_095

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1DECKTOPCARD:SOR_046
P1DECKCOUNT:1
P1DISCARDCOUNT:1
P1BASE:EPICUSED

---

# EpicMill3PassReturn
#// LAW_026 Shipbreaking Yard — the "you may return" is optional. P1 mills the top 3 (SOR_046/SOR_095/
#// SOR_128) then passes the return, so all 3 stay in the discard pile and the 4th card (SOR_237) is now
#// the top of the deck. Deck count 1, discard 3. Epic consumed.

## GIVEN
CommonSetup: rbw/grw/{
  myBase:LAW_026
}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP1Deck: SOR_237

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:PASS

## EXPECT
P1DECKTOPCARD:SOR_237
P1DECKCOUNT:1
P1DISCARDCOUNT:3
P1BASE:EPICUSED

---

# EpicEmptyDeckNoOp
#// LAW_026 Shipbreaking Yard — the Epic Action can be used with an empty deck: nothing is discarded, there
#// is no return decision, and the Epic Action is still consumed (once per game).

## GIVEN
CommonSetup: rbw/grw/{
  myBase:LAW_026
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>UseBaseAbility

## EXPECT
P1DECKCOUNT:0
P1DISCARDCOUNT:0
P1BASE:EPICUSED
P1NODECISION

---

# P2Seat_EpicMillsItsOwnDeck
#// LAW_026 — control axis. A BASE can never change control, so seat resolution is the only observable
#// form of the owner-vs-controller question here: "Discard 3 cards from YOUR deck ... return a card
#// discarded this way to the top of YOUR deck" must run off the seat that OWNS the base, and every
#// other section drives the Epic Action from seat 1 only — so a deck lookup pinned to P1 would pass
#// them all unchanged.
#// Here the Shipbreaking Yard belongs to P2 and P2 uses it. The two decks are made distinguishable:
#// P2's holds four DIFFERENT cards while P1's holds four copies of SOR_164, so a mill on the wrong
#// side is unmissable. P2 mills SOR_046/SOR_095/SOR_128 and returns SOR_046 (myDiscard-0, written
#// from P2's seat = P2's discard) to the top of P2's deck — leaving P2 on deck 2 / discard 2 with
#// SOR_046 on top, P2's Epic spent, and P1's deck and discard completely untouched (4 / 0).
#//
#// COVERAGE: offer=the return pool is the 3 cards just milled — EpicMill3ReturnTop returns one and
#//           EpicMill3PassReturn declines; EpicMillOnly2InDeck shrinks the pool with the deck ·
#//           decline=EpicMill3PassReturn ("you MAY return") · control=this section (a base's "your
#//           deck" resolves from the base's own seat, proven by driving the Epic from seat 2 with
#//           both decks distinguishable) · boundary=EpicMillOnly2InDeck (fewer than 3 cards) +
#//           EpicEmptyDeckNoOp (nothing to mill) · reqboundary=the return pick is answered on a later
#//           request in every section.

## GIVEN
CommonSetup: bbw/rbw/{theirBase:LAW_026}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2Deck: SOR_046
WithP2Deck: SOR_095
WithP2Deck: SOR_128
WithP2Deck: SOR_237
WithP1Deck: SOR_164
WithP1Deck: SOR_164
WithP1Deck: SOR_164
WithP1Deck: SOR_164

## WHEN
- P2>UseBaseAbility
- P2>AnswerDecision:myDiscard-0

## EXPECT
P2DECKTOPCARD:SOR_046
P2DECKCOUNT:2
P2DISCARDCOUNT:2
P2BASE:EPICUSED
P1DECKCOUNT:4
P1DISCARDCOUNT:0
