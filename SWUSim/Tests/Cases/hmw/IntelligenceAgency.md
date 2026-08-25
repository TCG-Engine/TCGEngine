# WhenPlayed_LooksAndDiscards_OpponentDraws
#// HMW_205 Intelligence Agency (Cunning/Villainy, Fortification, cost 1, UPGRADE) —
#// "Fortify (Attach this to your base, not a unit.)
#//  Attached base gains: 'You may look at the top card of your deck at any time.'
#//  When Played: Look at an opponent's hand. You may discard a card from it. If you do, they draw a card."
#// COVERAGE: offer=OfferIsTheOpponentsWholeHand · negative=Decline_NoDiscardNoDraw +
#//           EmptyOpponentHand_NoPromptAtAll · boundary=N/A (no threshold, no amount — the only
#//           quantities are one card discarded and one drawn) · control=N/A (an upgrade on your OWN
#//           base; no take-control effect targets a base or its Fortify upgrades, and "an opponent" is
#//           simply the other seat) · reqboundary=RequestBoundary_AcrossTheDiscardPick ·
#//           decline=Decline_NoDiscardNoDraw
#// ⚠ THE THIRD CLAUSE IS SHD_184 BAZINE NETAL'S, WORD FOR WORD, and reuses its shape:
#//   SWULookAtOpponentHand (which logs the private reveal and returns theirHand-N) feeding an
#//   MZMAYCHOOSE — the pending theirHand decision IS the reveal the client renders.
#// ⚠ CLAUSE 2 IS NOT IMPLEMENTED — "attached base gains: you may look at the top card of your deck at
#//   any time" is a CONTINUOUS VISIBILITY permission and the engine has no such capability (grep finds
#//   none), nor does the harness have any visibility assertion to test one with. Same documented family
#//   as LAW_094 Hondo's visibility clause: a deferral with a written reason, not a red. Everything below
#//   covers clauses 1 and 3.
#// Here: the opponent holds two cards, one is discarded, and they draw a replacement — so their HAND
#// COUNT is unchanged and only the deck and discard move. Asserting the hand count alone would pass
#// against a card that did nothing at all.

## GIVEN
CommonSetup: yyk/rrk/{myResources:1;theirhandCardIds:SOR_095,SEC_080}
P1OnlyActions: true
WithP1Hand: HMW_205
WithP2Deck: [SOR_046 SOR_128]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirHand-0

## EXPECT
P2HANDCOUNT:2
P2DECKCOUNT:1
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_095
P1NODECISION

---

# Decline_NoDiscardNoDraw
#// HMW_205 — "You MAY discard … IF YOU DO, they draw." Declining must do neither: their hand, deck and
#// discard are all exactly as they started. A draw wired unconditionally would show up as a deck of 1.

## GIVEN
CommonSetup: yyk/rrk/{myResources:1;theirhandCardIds:SOR_095,SEC_080}
P1OnlyActions: true
WithP1Hand: HMW_205
WithP2Deck: [SOR_046 SOR_128]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2HANDCOUNT:2
P2DECKCOUNT:2
P2DISCARDCOUNT:0
P1NODECISION

---

# EmptyOpponentHand_NoPromptAtAll
#// HMW_205 — with nothing to look at there is nothing to offer, so no prompt is raised at all (the
#// SEC_186/SEC_210/SEC_260 family: a look-at-hand ability against an empty hand must not ask a question
#// that cannot be answered). The upgrade still plays and still attaches.
#// (Green before implementation — an absence guard.)
#// ⚠ MEASURED: deleting the card's own `if (empty($targets)) return;` leaves this GREEN, because
#// SWUQueueMayChooseTarget already no-ops on an empty pool. So this section guards the shared helper's
#// BEHAVIOUR, not that explicit line — the line is belt-and-braces that states the intent locally and
#// would start mattering the moment the helper changed. Recorded so nobody reads it as proof of the
#// guard it sits next to.

## GIVEN
CommonSetup: yyk/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: HMW_205
WithP2Deck: [SOR_046 SOR_128]

## WHEN
- P1>PlayHand:0

## EXPECT
P2HANDCOUNT:0
P2DECKCOUNT:2
P2DISCARDCOUNT:0
P1NODECISION

---

# OfferIsTheOpponentsWholeHand
#// HMW_205 — the POOL, left pending: every card in the OPPONENT's hand and nothing of the caster's own
#// (P1 is holding a spare card here precisely so a my-hand slip would show). Three of their cards so the
#// offer is real rather than auto-resolving.

## GIVEN
CommonSetup: yyk/rrk/{myResources:1;theirhandCardIds:SOR_095,SEC_080,SOR_046}
P1OnlyActions: true
WithP1Hand: HMW_205
WithP1Hand: SOR_095
WithP2Deck: [SOR_046 SOR_128]

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:theirHand-0&theirHand-1&theirHand-2
P1HASDECISION

---

# DiscardedCardGoesToTHEIRDiscardPile
#// HMW_205 — ownership. The discarded card is the OPPONENT'S, so it lands in THEIR discard pile, not the
#// caster's, and it is stamped as coming FROM HAND. P1's own discard holds nothing (the upgrade is on
#// the base, not in a pile).

## GIVEN
CommonSetup: yyk/rrk/{myResources:1;theirhandCardIds:SEC_080,SOR_095}
P1OnlyActions: true
WithP1Hand: HMW_205
WithP2Deck: [SOR_046 SOR_128]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirHand-0

## EXPECT
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SEC_080
P2DISCARDUNIT:0:FROM:HAND
P1DISCARDCOUNT:0

---

# Fortify_AttachesToTheBase_ProvenByABaseUpgradeReader
#// HMW_205 — the FORTIFY clause. The harness has no base-upgrade-count assertion, so this proves the
#// attachment through a card that READS that count: HMW_066 Carrion Spike gets +1/+0 per upgrade on your
#// base, so it goes 3 -> 4 power the moment Intelligence Agency lands on the base. If Fortify attached it
#// to a unit (or nowhere) the Spike would stay at 3.
#// The opponent's hand is empty on purpose so the When Played raises no prompt and this section is only
#// about where the upgrade went.

## GIVEN
CommonSetup: yyk/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: HMW_205
WithP1SpaceArena: HMW_066:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:HMW_066
P1SPACEARENAUNIT:0:POWER:4

---

# EmptyDeck_TheReplacementDrawIsTheDeckOutPenalty
#// HMW_205 — "they draw a card" is unconditional once the discard happens, so against an EMPTY deck it
#// resolves as the CR 6.1 deck-out penalty (3 damage to their base) rather than silently doing nothing.
#// Their hand therefore ends at 1, not 2.

## GIVEN
CommonSetup: yyk/rrk/{myResources:1;theirhandCardIds:SOR_095,SEC_080}
P1OnlyActions: true
WithP1Hand: HMW_205

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirHand-0

## EXPECT
P2HANDCOUNT:1
P2DISCARDCOUNT:1
P2BASEDMG:3

---

# RequestBoundary_AcrossTheDiscardPick
#// HMW_205 — the request-boundary cell. The discard choice ends the request in production, so the chosen
#// card and the "they draw" rider must both be resolved when the answer arrives. Same flow and
#// assertions as the first section with the boundary inserted before the pick.

## GIVEN
CommonSetup: yyk/rrk/{myResources:1;theirhandCardIds:SOR_095,SEC_080}
P1OnlyActions: true
WithP1Hand: HMW_205
WithP2Deck: [SOR_046 SOR_128]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirHand-0

## EXPECT
P2HANDCOUNT:2
P2DECKCOUNT:1
P2DISCARDCOUNT:1
P1NODECISION

---

# TwinSuns_LooksAtTheCHOSENSeatsHand
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-24. "Look at AN OPPONENT's hand. You may discard a card from it.
#// If you do, they draw a card." OtherPlayer() read a hand the caster never chose.
#// ⚠⚠ PREVIEW-SET ASSUMPTION, FLAGGED: HMW is NOT in card-specific-rulings.md — that database covers
#// RELEASED sets only. The reading comes from the closest released analogue, which is EXACT: SHD_184
#// Bazine Netal prints this clause word for word and DOES carry "If there are multiple opponents, the
#// controlling player chooses which one will be 'an opponent.'" Re-check when HMW releases.
#// ⚠ FILTER to opponents holding a card — an empty hand has nothing to look at or discard.
#// SEAT 3 is picked; P1 discards from seat 3 and SEAT 3 draws the replacement. Seat 2 untouched.
#// Mutation check: drop the $opp argument to SWULookAtOpponentHand and this reds.

## GIVEN
CommonSetup: yyk/rrk/{myResources:1}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1Hand: HMW_205
WithP2Hand: [SOR_095 SEC_080]
WithP3Hand: [SOR_095 SEC_080]
WithP3Deck: [SOR_046 SOR_128]
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P3
- P1>AnswerDecision:p3Hand-0

## EXPECT
SEATCOUNT:4
P3HANDCOUNT:2
P3DISCARDCOUNT:1
P3DECKCOUNT:1
P2HANDCOUNT:2
P2DISCARDCOUNT:0
