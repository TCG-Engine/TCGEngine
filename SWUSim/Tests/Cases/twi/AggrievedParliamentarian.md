# ShuffleOppDiscard
#// TWI_252 Aggrieved Parliamentarian (Unit, Ground) — "When Played: Choose an opponent. They shuffle their
#// discard pile and put it on the bottom of their deck." P2's 1-card discard moves to the bottom of their deck.
## GIVEN
CommonSetup: rrk/bbw/{myResources:2;handCardIds:TWI_252}
P1OnlyActions: true
WithP2Discard: SOR_095
WithP2Deck: [SOR_046]
## WHEN
- P1>PlayHand:0
## EXPECT
P2DISCARDCOUNT:0
P2DECKCOUNT:2

---

# TwinSuns_PickerOffersOnlyOpponentsWithADiscardPile
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-23 (Pass 1, PROMPT). "Choose an opponent" is a real choice above
#// two seats; OtherPlayer() picked one silently. Auto-resolves at one eligible, so Premier is untouched.
#//
#// ⚠⚠ ELIGIBILITY IS THE OPPOSITE OF TWI_222's, one card away in the same set. Here an opponent with an
#// EMPTY discard pile is a TRUE no-op — shuffling nothing into nothing — so they are correctly filtered
#// OUT of the menu (invariant I2: never ask a question whose answers do nothing). On TWI_222 an empty
#// hand is a guaranteed payoff and must NOT be filtered. Same sentence, opposite rule: decide eligibility
#// from what the effect DOES to the chosen seat, never from the shape of the sentence.
#//
#// Seat 2 has an EMPTY discard and must NOT be offered. Seats 3 and 4 both have one, so the menu is
#// exactly P3&P4 — and notably NOT P1 (a menu built from GetLiveSeatsArray() instead of OpponentsOf()
#// would offer the caster their own seat).
#// P1 picks SEAT 4; seat 4's discard goes to the bottom of their deck and seat 3's is untouched.
#// ⚠ A 2-player version CANNOT FAIL — one opponent means no menu.
#// Mutation check: revert to OtherPlayer() and this reds; drop the $eligible filter and P2 appears in the
#// OPTIONHAS assertion.

## GIVEN
CommonSetup: rrk/bbw/{myResources:2;handCardIds:TWI_252}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP3Discard: SOR_095
WithP3Deck: [SOR_046]
WithP4Discard: SOR_095
WithP4Deck: [SOR_046]
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1HASDECISION
P1OPTIONHAS:P3
P1OPTIONHAS:P4
P1OPTIONNOT:P2
P1OPTIONNOT:P1
