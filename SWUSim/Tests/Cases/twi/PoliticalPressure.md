# OpponentDeclines_CreatesDroids
#// TWI_222 Political Pressure (Event, cost 1, Cunning) — "Choose an opponent. They may discard a
#// random card from their hand. If they don't, create 2 Battle Droid tokens." The opponent DECLINES
#// (AnswerDecision:NO) → the caster creates 2 Battle Droid tokens; opponent's hand is untouched.
#// Driven with WithActivePlayer:1 (not P1OnlyActions) so P2 can answer the cross-player YESNO.

## GIVEN
CommonSetup: yyk/grw/{myResources:1;handCardIds:TWI_222;theirhandCardIds:SOR_095}
WithActivePlayer: 1

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P2HANDCOUNT:1

---

# OpponentDiscards_NoDroids
#// TWI_222 Political Pressure — the opponent ACCEPTS (AnswerDecision:YES) and discards a random card
#// from their (1-card) hand → no Battle Droids are created. With exactly 1 card in hand the "random"
#// discard is deterministic.

## GIVEN
CommonSetup: yyk/grw/{myResources:1;handCardIds:TWI_222;theirhandCardIds:SOR_095}
WithActivePlayer: 1

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P2HANDCOUNT:0
P2DISCARDCOUNT:1

---

# TwinSuns_TheHELLBENTSeatSTAYSInThePicker
#// ⚠ THE ELIGIBILITY CELL — added 2026-08-23 (Pass 1, PROMPT). This asserts the MENU, and it is the only
#// thing that pins the eligibility decision.
#//
#// ⚠⚠ WHY A SEPARATE SECTION: the outcome section below CANNOT pin this. The harness validates candidate
#// lists for MZCHOOSE but NOT for OPTIONCHOOSE, so answering "P4" is accepted even when seat 4 has been
#// filtered out of the menu — the effect then resolves identically and the outcome test passes against
#// the wrong eligibility. Verified by mutation: adding SWUOpponentsWithCards() left the outcome section
#// green. Only an assertion on the OPTION LIST catches it.
#//
#// THE RULE BEING PINNED, which is the OPPOSITE of the sweep's reflex: everywhere else "an opponent
#// discards" filters to opponents holding a card, because a pick against an empty hand is a choice among
#// nothing. Here an empty hand is a GUARANTEED PAYOFF — "They MAY discard… IF THEY DON'T, create 2 Battle
#// Droid tokens" — so aiming at a hellbent seat is the card's STRONGEST line. $eligible must stay null.
#// ⚠ Compare TWI_252, one card away in the same set, which needs the OPPOSITE treatment.
#//
#// Seats 2 and 3 hold cards; SEAT 4 IS EMPTY-HANDED. Seat 4 must still appear in the picker — and the
#// caster's own seat must not.
#// Mutation check: pass SWUOpponentsWithCards() as $eligible and P1OPTIONHAS:P4 reds.

## GIVEN
CommonSetup: yyk/grw/{myResources:1;handCardIds:TWI_222}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP2Hand: SOR_095
WithP3Hand: SOR_095
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

# TwinSuns_PickingTheHellbentSeatGivesYouTheDroids
#// ⚠ THE OUTCOME half of the pair above. "Choose an opponent" is a REAL choice above two seats;
#// OtherPlayer() picked one silently and the caster was never asked. The picker auto-resolves to an
#// invisible PASSPARAMETER at one eligible opponent, so Premier is untouched (I1).
#// P1 picks SEAT 4, who cannot discard — so "they don't" and P1 gets the 2 Battle Droids, with no YES/NO
#// ever raised (there is nothing for seat 4 to decide). Seats 2 and 3 keep their cards.
#// Under the old code the pick went to seat 2 automatically and this line was unavailable.
#// ⚠ This section does NOT pin eligibility — see the section above for why.
#// Mutation check: revert to OtherPlayer() and this reds.

## GIVEN
CommonSetup: yyk/grw/{myResources:1;handCardIds:TWI_222}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP2Hand: SOR_095
WithP3Hand: SOR_095
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P4

## EXPECT
SEATCOUNT:4
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P2HANDCOUNT:1
P3HANDCOUNT:1
P4HANDCOUNT:0
