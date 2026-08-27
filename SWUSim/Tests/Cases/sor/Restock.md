# DiscardToDeckBottom
#// COVERAGE: offer=OfferSpansBothDiscardPilesIncludingItself (exact pool asserted, pending —
#//           both piles plus the just-played Restock itself) · decline=ChooseNothing_EverythingStays
#//           ("up to 4" includes zero) · boundary=FourFromOwnDiscard_FifthStays (the 4-cap: five
#//           candidates in one pile, exactly four may leave) · control=N/A (cards are routed by the
#//           OWNER of the discard pile they sit in — OpponentDiscardGoesToTheirDeckBottom proves the
#//           per-owner routing; nothing persists that a control change could touch) · reqboundary=the
#//           multi-pick answer is decoded in one handler after the single choice, no state read spans
#//           a second decision
#// SOR_252 Restock (Event, cost 1) — "Choose up to 4 cards in a discard pile. Put them
#// on the bottom of their owner's deck in a random order." P1's discard is seeded with
#// three cards; playing Restock adds the event itself to the discard (4 total). Choosing
#// the first two seeded cards (SOR_095, SOR_046) sends them to the deck bottom, leaving
#// SOR_032 and the spent Restock (2) — with SOR_032 first.

## GIVEN
CommonSetup: ggk/ggk/{myResources:1;handCardIds:SOR_252;discardCardIds:SOR_095,SOR_046,SOR_032}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0&myDiscard-1

## EXPECT
P1DISCARDCOUNT:2
P1DISCARDUNIT:0:CARDID:SOR_032

---

# OfferSpansBothDiscardPilesIncludingItself
#// SOR_252 Restock — the initial pool is "cards in a discard pile": BOTH players' discards are
#// offered, and the just-played Restock itself (already in its owner's discard when the choice is
#// built, at index 3) is a legal pick. Left pending to assert the exact offer.

## GIVEN
CommonSetup: ggk/ggk/{myResources:1;handCardIds:SOR_252;discardCardIds:SOR_095,SOR_046,SOR_032;theirDiscardCardIds:SOR_140,SOR_225,SOR_046}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myDiscard-0&myDiscard-1&myDiscard-2&myDiscard-3&theirDiscard-0&theirDiscard-1&theirDiscard-2

---

# FourFromOwnDiscard_FifthStays
#// SOR_252 Restock — "up to 4" is a hard cap. Five cards sit in P1's discard after the play (four
#// seeded + the spent Restock); choosing four of them bottoms those four (random order, so only
#// counts are asserted) and the unchosen fifth (SOR_032, originally index 2) stays in the discard.

## GIVEN
CommonSetup: ggk/ggk/{myResources:1;handCardIds:SOR_252;discardCardIds:SOR_095,SOR_046,SOR_032,SOR_140}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0&myDiscard-1&myDiscard-3&myDiscard-4

## EXPECT
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_032
P1DECKCOUNT:4
P1NODECISION

---

# OpponentDiscardGoesToTheirDeckBottom
#// SOR_252 Restock — cards chosen from the OPPONENT's discard go to the bottom of THEIR owner's
#// deck, not the caster's: P2's three discarded cards all leave P2's discard for P2's deck, while
#// P1's own discard (three seeds + the spent Restock) is untouched.

## GIVEN
CommonSetup: ggk/ggk/{myResources:1;handCardIds:SOR_252;discardCardIds:SOR_095,SOR_046,SOR_032;theirDiscardCardIds:SOR_140,SOR_225,SOR_046}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirDiscard-0&theirDiscard-1&theirDiscard-2

## EXPECT
P2DISCARDCOUNT:0
P2DECKCOUNT:3
P1DISCARDCOUNT:4
P1DECKCOUNT:0
P1NODECISION

---

# ChooseNothing_EverythingStays
#// SOR_252 Restock — "up to 4" includes ZERO: declining the whole choice leaves every card exactly
#// where it was (both discards intact, no deck movement); the event is still spent.

## GIVEN
CommonSetup: ggk/ggk/{myResources:1;handCardIds:SOR_252;discardCardIds:SOR_095,SOR_046,SOR_032;theirDiscardCardIds:SOR_140,SOR_225,SOR_046}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1DISCARDCOUNT:4
P2DISCARDCOUNT:3
P1DECKCOUNT:0
P2DECKCOUNT:0
P1RESAVAILABLE:0
P1NODECISION

---

# MixedPilePicks_OnlyFirstPileHonored
#// "Choose up to 4 cards in A discard pile" — one pile only. A mixed answer is not a legal choice:
#// the first pick fixes the pile and the cross-pile pick is ignored. Here myDiscard-0 then
#// theirDiscard-0: only P1's card moves to P1's deck bottom; P2's discard and deck are untouched.

## GIVEN
CommonSetup: rrw/bbk/{myResources:1}
P1OnlyActions: true
WithP1Discard: [SOR_095]
WithP2Discard: [SOR_164]
WithP1Hand: SOR_252
WithP1Deck: [SOR_046]
WithP2Deck: [SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0&theirDiscard-0

## EXPECT
P1DECKCOUNT:2
P2DECKCOUNT:1
P2DISCARDCOUNT:1
P1DISCARDCOUNT:1

---

# TwinSuns_ReturnsToTheCHOSENPilesOwner
#// ⚠ TWIN SUNS SWEEP PASS 2 (2026-08-27) — batch 1, "resolve the seat from the mzID".
#// The seat came from `(strpos($mz,'my') === 0) ? $player : OtherPlayer/GetOpponent(...)`, which collapses
#// EVERY non-"my" mzID to seat 2. The chosen mzID already names its seat, so SWUMzOwner() reads it.
#//
#// "Choose up to 4 cards in a discard pile. Put them on the bottom of THEIR OWNER'S deck." Both picks come
#// from seat 4's pile and must return to seat 4's deck; seat 2's deck stays empty.
## GIVEN
CommonSetup: ggk/ggk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Resources: 1
WithP1Hand: SOR_252
WithP4Discard: SOR_095
WithP4Discard: SOR_046
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p4Discard-0&p4Discard-1
## EXPECT
SEATCOUNT:4
P4DISCARDCOUNT:0
P4DECKCOUNT:2
P2DECKCOUNT:0
