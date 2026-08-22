# EachPlayer_DiscardsDownToThree
#// HMW_161 Raze to Ruin (Event, cost 2, [Aggression][Villainy], traits Disaster/Plan)
#// Text: Each player discards all but 3 cards from their hand.
#//
#// The 3-card sibling of SOR_174 Smoke and Cinders ("all but 2 of their choice") and built on the same
#// SWUKeepNDiscardRest / SOR_174#0 seam: each player is handed a keep-N MZMULTICHOOSE over their OWN
#// hand, and everything not kept is discarded.
#//
#// ⚠ "EACH PLAYER" INCLUDES THE CASTER. A symmetrical card read as "each opponent" is a different (and
#// much better) card, and the caster's own half is the one an implementation is most likely to skip.
#//
#// COVERAGE: offer=YouPickWHICHThreeYouKeep (P1DECISIONTOOLTIP on the pending keep-3) ·
#//           negative=AtExactlyThree_NoPromptNoDiscard + FewerThanThree_NothingHappens (the "all but"
#//             gate must NOT fire at or below the threshold) ·
#//           boundary=AtExactlyThree_NoPromptNoDiscard vs AtFour_DiscardsExactlyOne (the PAIR) ·
#//           control=N/A (an event resolves once for its caster; hands are owner-scoped and there is
#//             no permanent to take control of) · reqboundary=TwinSuns_AcrossTheRequestBoundary ·
#//           decline=N/A (mandatory — "discards", not "may discard"; the keep-N is a fixed-size
#//             MZMULTICHOOSE with no pass)
#//
#// P1 holds Raze + 5, P2 holds 5. After the play both sit at exactly 3.

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: [HMW_161 SOR_095 SOR_046 SEC_080 SOR_128 SOR_237]
WithP2Hand: [SOR_095 SOR_046 SEC_080 SOR_128 SOR_237]
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0&myHand-1&myHand-2
- P2>AnswerDecision:myHand-0&myHand-1&myHand-2

## EXPECT
P1HANDCOUNT:3
P2HANDCOUNT:3
P2DISCARDCOUNT:2

---

# TheJustPlayedEventDoesNotCountAgainstYourThree
#// ⚠ THE SHARPEST SECTION IN THE FILE. Raze to Ruin is ALREADY OUT OF HAND when its own effect
#// resolves — ActivateCard removes the event and moves it to the discard before dispatching the When
#// Played, but the removed entry lingers in the hand zone until a cleanup compacts it.
#// So a hand-count taken naively sees FOUR cards here and prompts the caster to pitch one more.
#// P1 holds Raze + exactly 3. Playing it leaves exactly 3 — the caster must get NO prompt and keep all
#// three. The discard holds only Raze itself.
#// (SWUKeepNDiscardRest runs CleanupRemovedCards before reading the hand, which is what makes this
#// work; the section exists so that stays true.)

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: [HMW_161 SOR_095 SOR_046 SEC_080]
WithP2Hand: [SOR_095 SOR_046]
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:3
P1DISCARDCOUNT:1
P1NODECISION
P2HANDCOUNT:2
P2NODECISION

---

# AtExactlyThree_NoPromptNoDiscard
#// BOUNDARY, low half. "All but 3" at exactly 3 is a no-op: no decision, no discard.
#// Pairs with AtFour_DiscardsExactlyOne — the positive alone passes for ANY threshold value, and a
#// lone "nothing happens at 3" proves nothing without "one goes at 4".

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: [HMW_161 SOR_095 SOR_046 SEC_080]
WithP2Hand: [SOR_095 SOR_046 SEC_080]
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
P2HANDCOUNT:3
P2DISCARDCOUNT:0
P2NODECISION

---

# AtFour_DiscardsExactlyOne
#// BOUNDARY, high half. Four cards → keep 3, discard exactly 1 (never 2, never the whole hand).

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: [HMW_161 SOR_095 SOR_046 SEC_080]
WithP2Hand: [SOR_095 SOR_046 SEC_080 SOR_128]
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0&myHand-1&myHand-2

## EXPECT
P2HANDCOUNT:3
P2DISCARDCOUNT:1

---

# FewerThanThree_AndEmptyHand_NothingHappens
#// The NEGATIVE below the gate, both flavours in one board: P2 holds a single card (keeps it, no
#// prompt) — and the empty-hand case is covered by the Twin Suns section's P4.
#// ⚠ A player with fewer than 3 must not be prompted at all: a keep-3 offered over a 1-card hand is
#//   an unanswerable decision that would stall that seat's queue.

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: [HMW_161 SOR_095 SOR_046 SEC_080]
WithP2Hand: [SOR_095]
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
P2HANDCOUNT:1
P2DISCARDCOUNT:0
P2NODECISION

---

# YouPickWHICHThreeYouKeep
#// ⚠ THE OFFER CELL, plus the "of their choice" reading. The printed text says only "discards all but
#// 3 cards from their hand" — no "(of their choice)", which its SOR_174 sibling does carry. It is
#// still the player's own choice: a hand is a HIDDEN zone and nothing here says "at random", so the
#// owner picks. This section pins the prompt itself (left pending, tooltip read off it) and the next
#// one pins that the ANSWER is honoured rather than the first N being kept.

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: [HMW_161 SOR_095 SOR_046 SEC_080 SOR_128 SOR_237]
WithP2Hand: [SOR_095 SOR_046]
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Keep_3_cards_-_discard_the_rest

---

# TheKeptCardsAreTheONESYouNamed
#// The answer must be READ, not assumed. P1 keeps hand slots 2, 3 and 4 — so the cards that survive
#// are SEC_080 / SOR_128 / SOR_237 and the two DISCARDED are SOR_095 and SOR_046.
#// ⚠ An implementation that simply kept the first 3 passes every count-only section in this file.
#// (After the play, Raze is gone from hand, so myHand-0..4 are the five remaining cards in order.)

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: [HMW_161 SOR_095 SOR_046 SEC_080 SOR_128 SOR_237]
WithP2Hand: [SOR_095 SOR_046]
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-2&myHand-3&myHand-4

## EXPECT
P1HANDCOUNT:3
P1HANDCARD:0:SEC_080
P1HANDCARD:1:SOR_128
P1HANDCARD:2:SOR_237

---

# TwinSuns_EVERYSeatDiscardsDownToThree
#// ⚠ THE SEAT-COUNT CELL, and the reason this card is not just "SOR_174 with a 3". "Each player" in a
#// four-seat game is FOUR hands, not two. Its sibling SOR_174 resolves `OtherPlayer($player)` and the
#// caster only, so a copy of that shape silently leaves seats 3 and 4 holding full hands — the
#// two-seat-hardcode family, and a 2-player version of this test cannot fail.
#// P1 (caster) and P2 hold 5 each, P3 holds 4, P4 holds none. Every seat ends at or below 3, and P4's
#// empty hand must not raise a decision.

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: [HMW_161 SOR_095 SOR_046 SEC_080 SOR_128 SOR_237]
WithP2Hand: [SOR_095 SOR_046 SEC_080 SOR_128 SOR_237]
WithP3Hand: [SOR_095 SOR_046 SEC_080 SOR_128]
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0&myHand-1&myHand-2
- P2>AnswerDecision:myHand-0&myHand-1&myHand-2
- P3>AnswerDecision:myHand-0&myHand-1&myHand-2

## EXPECT
SEATCOUNT:4
P1HANDCOUNT:3
P2HANDCOUNT:3
P3HANDCOUNT:3
P4HANDCOUNT:0
P2DISCARDCOUNT:2
P3DISCARDCOUNT:1
P4NODECISION

---

# TwinSuns_AcrossTheRequestBoundary
#// ⚠ THE REQUEST-BOUNDARY CELL. Four separate keep-3 decisions, one per seat, each answered in its own
#// HTTP request — so every player's continuation resumes in a FRESH process. Anything the effect held
#// in an in-memory global between queueing the prompts and resolving them is gone by then, and the
#// discards silently do not happen.
#// Same board as the Twin Suns section with a boundary inserted before each answer.

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: [HMW_161 SOR_095 SOR_046 SEC_080 SOR_128 SOR_237]
WithP2Hand: [SOR_095 SOR_046 SEC_080 SOR_128 SOR_237]
WithP3Hand: [SOR_095 SOR_046 SEC_080 SOR_128]
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myHand-0&myHand-1&myHand-2
- P2>SimulateRequestBoundary
- P2>AnswerDecision:myHand-0&myHand-1&myHand-2
- P3>SimulateRequestBoundary
- P3>AnswerDecision:myHand-0&myHand-1&myHand-2

## EXPECT
P1HANDCOUNT:3
P2HANDCOUNT:3
P3HANDCOUNT:3
P4HANDCOUNT:0
