# OppDeclines
#// TS26_33 Kouhun Assassination — if the opponent declines to discard ("may"), the rider does not happen:
#// no debuff, the opponent keeps their card and unit.
## GIVEN
CommonSetup: byk/rrk/{myResources:3;handCardIds:TS26_33;theirhandCardIds:SOR_095}
WithP2GroundArena: SEC_080:1:0
WithActivePlayer: 1
## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:-
## EXPECT
P2HANDCOUNT:1
P2GROUNDARENACOUNT:1

---

# OppDiscardsThenDebuff
#// TS26_33 Kouhun Assassination (Event, cost 3) — An opponent may discard a card from their hand. If they
#// do, give a non-Vehicle unit -8/-8 for this phase. The opponent discards their card, then the caster
#// debuffs a non-Vehicle enemy unit (SEC_080, 3/3) to death.
## GIVEN
CommonSetup: byk/rrk/{myResources:3;handCardIds:TS26_33;theirhandCardIds:SOR_095}
WithP2GroundArena: SEC_080:1:0
WithActivePlayer: 1
## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2HANDCOUNT:0
P2GROUNDARENACOUNT:0

---

# AnOpponentWithAnEmptyHandCannotPayAndNothingHappens
#// TS26_33 Kouhun Assassination — "An opponent MAY DISCARD a card from their hand. IF THEY DO, give a
#// non-Vehicle unit -8/-8." With P2 holding nothing there is no discard to make, so no offer is raised and
#// SEC_080 keeps its 3 power.

## GIVEN
CommonSetup: byk/rrk/{myResources:3;handCardIds:TS26_33}
SkipPreGame: true
WithActivePlayer: 1
WithP2GroundArena: SEC_080:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:POWER:3
P1NODECISION

---

# WithOnlyVEHICLESInPlayThereIsNothingToDebuff
#// TS26_33 Kouhun Assassination — the payoff needs a NON-VEHICLE unit. P2 holds a card but the only unit
#// on the board is the Vehicle ASH_261, so the exchange never starts: their hand is untouched and the Pod
#// keeps its 3 power.

## GIVEN
CommonSetup: byk/rrk/{myResources:3;handCardIds:TS26_33;theirhandCardIds:SOR_095}
SkipPreGame: true
WithActivePlayer: 1
WithP2GroundArena: ASH_261:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P2HANDCOUNT:1
P2GROUNDARENAUNIT:0:POWER:3

---

# TwinSuns_PickerOffersOnlyOpponentsHoldingACard
#// ⚠ THE ELIGIBILITY CELL — added 2026-08-23 (Pass 1, PROMPT). Asserts the MENU.
#// ⚠ "An opponent (OF YOUR CHOICE) may discard…" — the parenthesised phrase is NOT reminder text to be
#// stripped. It is the card explicitly settling the question the rest of this sweep has to infer: the
#// CASTER picks the opponent, then that opponent decides whether to discard.
#// FILTER IS CORRECT HERE because the chosen player is asked to DO something — "may discard a card from
#// THEIR hand". An empty-handed opponent cannot discard, cannot satisfy "if they do", and cannot enable
#// the -8/-8 rider: a choice among nothing.
#// ⚠ Contrast TWI_222, whose "if they DON'T" clause makes an empty hand a PAYOFF that must stay eligible.
#//   Same "an opponent … discard" sentence, opposite rule — the difference is what happens when they can't.
#// Seats 2 and 3 hold a card; SEAT 4 IS EMPTY-HANDED and must NOT be offered.
#// Mutation check: drop the SWUOpponentsWithCards filter and P1OPTIONNOT:P4 reds.

## GIVEN
CommonSetup: byk/rrk/{myResources:3;handCardIds:TS26_33}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP2Hand: SOR_095
WithP3Hand: SOR_095
WithP2GroundArena: SEC_080:1:0
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1HASDECISION
P1OPTIONHAS:P2
P1OPTIONHAS:P3
P1OPTIONNOT:P4
P1OPTIONNOT:P1
