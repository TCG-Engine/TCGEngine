# BothDraw2
#// TS26_68 Arms Deal (Event, cost 2, Aggression) — You and an opponent each draw 2 cards.
## GIVEN
CommonSetup: rrk/rrk/{myResources:2;handCardIds:TS26_68}
WithP1Deck: [SEC_080 SOR_095 SOR_046]
WithP2Deck: [SEC_080 SOR_095 SOR_046]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1HANDCOUNT:2
P2HANDCOUNT:2
P1DECKCOUNT:1
P2DECKCOUNT:1

---

# ResolvesWithBOTHDecksEmpty
#// TS26_68 Arms Deal — "You and an opponent each draw 2 cards" with nothing to draw on either side. The
#// event still resolves into the discard and neither hand grows; each player instead eats the empty-deck
#// penalty of 3 base damage per undrawn card, so both bases end on 6.

## GIVEN
CommonSetup: rrk/rrk/{myResources:2;handCardIds:TS26_68}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P2HANDCOUNT:0
P1DISCARDCOUNT:1
P1BASEDMG:6
P2BASEDMG:6

---

# TwinSuns_TheEMPTYDECKSeatStaysInThePicker
#// ⚠ THE ELIGIBILITY CELL — added 2026-08-23 (Pass 1, PROMPT). Asserts the MENU; an outcome-only section
#// cannot pin eligibility because the harness does not validate OPTIONCHOOSE candidates.
#// "You and AN OPPONENT each draw 2" — the caster chooses who shares the draw; OtherPlayer() picked one
#// silently. Auto-resolves at one eligible opponent, so Premier is untouched (I1).
#// ⚠ NO FILTER: DoDrawCard ALWAYS does something to the chosen seat — either 2 cards enter their hand, or
#// their base takes damage for each card they cannot draw. So an EMPTY-DECK opponent is a legal and often
#// PREFERRED pick (it is base damage, not a fizzle), exactly like TWI_222's hellbent seat.
#// SEAT 4 has an EMPTY deck and must still be offered.
#// Mutation check: filter to opponents with a non-empty deck and P1OPTIONHAS:P4 reds.

## GIVEN
CommonSetup: rrk/rrk/{myResources:2;handCardIds:TS26_68}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1Deck: [SEC_080 SOR_095 SOR_046]
WithP2Deck: [SEC_080 SOR_095]
WithP3Deck: [SEC_080 SOR_095]
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1HASDECISION
P1OPTIONHAS:P2
P1OPTIONHAS:P3
P1OPTIONHAS:P4
P1OPTIONNOT:P1

---

# TwinSuns_OnlyTheCHOSENOpponentDraws
#// ⚠ THE OUTCOME half. Under the old code the second draw always went to one fixed seat, so the caster
#// could never choose who to share with — and above two seats it fed a player they may not have wanted to.
#// P1 picks SEAT 3. P1 and seat 3 each draw 2; seats 2 and 4 draw NOTHING.
#// ⚠ A 2-player version CANNOT FAIL — one opponent means no choice to get wrong.
#// Mutation check: revert to OtherPlayer() and this reds.

## GIVEN
CommonSetup: rrk/rrk/{myResources:2;handCardIds:TS26_68}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1Deck: [SEC_080 SOR_095 SOR_046]
WithP2Deck: [SEC_080 SOR_095]
WithP3Deck: [SEC_080 SOR_095]
WithP4Deck: [SEC_080 SOR_095]
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P3

## EXPECT
SEATCOUNT:4
P1DECKCOUNT:1
P3DECKCOUNT:0
P3HANDCOUNT:2
P2DECKCOUNT:2
P2HANDCOUNT:0
P4DECKCOUNT:2
P4HANDCOUNT:0
