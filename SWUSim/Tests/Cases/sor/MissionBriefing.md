# ChooseOpponent_Draws2
#// SOR_171 Mission Briefing (Event, cost 3) — Choose a player. They draw 2 cards. P1 plays it
#// and (via the option-picker) chooses the opponent, so P2 draws 2 (P2 hand 0 → 2, deck −2).
#// COVERAGE: offer=both option branches exercised (Opponent in ChooseOpponent_Draws2, You in
#//           ChooseYou_Draws2 — each asserts the OTHER player's hand/deck untouched) ·
#//           decline=N/A (the player choice is mandatory) · control=N/A (no units involved) ·
#//           boundary=DeckShorterThanTwo_UndrawnCardBurnsThreeToThatBase (deck of 1 vs the deck of 2
#//           in ChooseOpponent_Draws2: the second, undrawable card burns 3 to the CHOSEN player's
#//           base) · reqboundary=both draw sections (play and choice answer span separate requests)

## GIVEN
CommonSetup: ggw/ggw/{myResources:5}
P1OnlyActions: true
WithP1Hand: SOR_171
WithP2Deck: SOR_128
WithP2Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P2HANDCOUNT:2
P2DECKCOUNT:0

---

# ChooseYou_Draws2
#// SOR_171 Mission Briefing — the "You" branch: P1 plays it and chooses THEMSELVES via the
#// option-picker, so P1 draws 2 (hand 0 → 2 after the event leaves, deck 3 → 1) while the
#// opponent's hand and deck are untouched.

## GIVEN
CommonSetup: ggw/ggw/{myResources:5}
P1OnlyActions: true
WithP1Hand: SOR_171
WithP1Deck: SOR_128
WithP1Deck: SOR_128
WithP1Deck: SOR_128
WithP2Deck: SOR_128
WithP2Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:You

## EXPECT
P1HANDCOUNT:2
P1DECKCOUNT:1
P2HANDCOUNT:0
P2DECKCOUNT:2

---

# FourSeats_ChooseAPlayerIncludesYourTeammate
#// SOR_171 "Choose a player. They draw 2 cards." — UNQUALIFIED, so the offer is every live seat, your
#// PARTNER included. SWUPlayerPickerLabels used to enumerate OpponentsOf($caster), which in Team Suns
#// deleted P3 from P1's own offer for all nine cards that share the helper. P1 hands its partner the draw.
#// ⚠ This section pins the OUTCOME, not the offer: OPTIONCHOOSE answers are not pool-validated by the
#// harness, so a picker that never listed P3 would still resolve this answer. See the plan doc's
#// "OPTIONCHOOSE answers are unvalidated" item.

## GIVEN
CommonSetup: rrw/rrw/{myResources:3}
SkipPreGame: true
WithTeams: true
P1OnlyActions: true
WithGamePhase: ActionPhase
WithP1Hand: SOR_171
WithP3Deck: [SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P3

## EXPECT
SEATCOUNT:4
P3HANDCOUNT:2
P3DECKCOUNT:2
P1HANDCOUNT:0

---

# DeckShorterThanTwo_UndrawnCardBurnsThreeToThatBase
#// SOR_171 Mission Briefing — the draw-2 boundary at N vs N-1. P2's deck holds exactly ONE card,
#// so the chosen player draws it and then cannot draw the second: per CR, a card you are unable to
#// draw deals 3 damage to YOUR OWN base instead, so the 3 lands on P2's base (the chosen player's),
#// not on the caster's. Contrast ChooseOpponent_Draws2, where a 2-card deck draws both and no base
#// is touched.

## GIVEN
CommonSetup: ggw/ggw/{myResources:5}
P1OnlyActions: true
WithP1Hand: SOR_171
WithP2Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P2HANDCOUNT:1
P2DECKCOUNT:0
P2BASEDMG:3
P1BASEDMG:0
P1HANDCOUNT:0
