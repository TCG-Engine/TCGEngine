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
#// ⚠ GIVEN repaired 2026-08-14 (assertions untouched): this used the key `myDiscardCardIds`, which the
#// CommonSetup parser does not recognise and silently DROPS — so SOR_095 was never seeded, P1's discard
#// held only the in-flight Commence Patrol, and the section was green while actually asserting the
#// self-recycle bug fixed below. The self-seeding key is `discardCardIds` (`theirDiscardCardIds` for
#// the opponent).

## GIVEN
CommonSetup: byw/byk/{myResources:5;handCardIds:JTL_205;discardCardIds:SOR_095}
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

---

# Offer_ExcludesTheInFlightCommencePatrolItself
#// JTL_205 — "Put ANOTHER card in a discard pile on the bottom of its owner's deck." ActivateCard appends
#// the event to its owner's discard BEFORE dispatching this When Played, so the in-flight copy is sitting
#// in the pile as a live entry while its own effect resolves. "Another" excludes it. With one seeded card
#// in each discard the offer is exactly those two — never the Commence Patrol at myDiscard-2.
#// The decision is left PENDING so the pool itself is asserted.

## GIVEN
CommonSetup: byw/byk/{myResources:5;handCardIds:JTL_205;discardCardIds:SOR_095;theirDiscardCardIds:SOR_108}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1DISCARDCOUNT:2
P1SELECTABLEEXACT:myDiscard-0&theirDiscard-0

---

# SecondCopyInDiscardIsStillALegalTarget
#// JTL_205 — "another" excludes only THIS copy, not the card by name. A SECOND Commence Patrol already
#// in the discard is a different card and remains selectable: P1 picks it, it goes to the bottom of P1's
#// deck and the X-Wing is still created. Guards the fix against the lazy over-filter (excluding every
#// JTL_205 in the pile rather than just the in-flight one).

## GIVEN
CommonSetup: byw/byk/{myResources:5;handCardIds:JTL_205;discardCardIds:JTL_205}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1DECKCOUNT:1
P1DISCARDCOUNT:1
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_T02

---

# OnlyItselfInDiscard_NoOfferNoXWing
#// JTL_205 — with the in-flight copy the ONLY card in either discard pile, "another card" has no legal
#// target: no offer is raised at all and no X-Wing is created. Before the 2026-08-14 fix this board
#// offered Commence Patrol itself, and answering it recycled the event into a free 2/2.

## GIVEN
CommonSetup: byw/byk/{myResources:5;handCardIds:JTL_205}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:1
P1DECKCOUNT:0

---

# TwinSuns_CardReturnsToITSOWNOwnersDeck
#// ⚠ TWIN SUNS SWEEP PASS 2 (2026-08-27) — batch 1, "resolve the seat from the mzID".
#// The seat came from `(strpos($mz,'my') === 0) ? $player : OtherPlayer/GetOpponent(...)`, which collapses
#// EVERY non-"my" mzID to seat 2. The chosen mzID already names its seat, so SWUMzOwner() reads it.
#//
#// "Put another card in a discard pile on the bottom of ITS OWNER'S deck." P1 picks a card from SEAT 4's
#// discard: it must land in SEAT 4's deck. Asserting P2's deck stays EMPTY is the half that matters —
#// under the old code the card was posted to seat 2, an untouched bystander.
## GIVEN
CommonSetup: byw/byk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Resources: 5
WithP1Hand: JTL_205
WithP4Discard: SOR_095
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p4Discard-0
## EXPECT
SEATCOUNT:4
P4DISCARDCOUNT:0
P4DECKCOUNT:1
P2DECKCOUNT:0
P1SPACEARENACOUNT:1
