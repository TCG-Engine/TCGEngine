# CreatesABeastForEachPlayer
#// COVERAGE: offer=TwinSuns_PickerOffersEveryOpponentAndNeverYou (the "an opponent" pool, asserted
#//           pending rather than answered) ·
#//           decline=N/A (nothing in the card is optional — no "may" in any clause; the only decline in
#//           reach is Jerjerrod's own YESNO, covered by Jerjerrod_DoublesAndBOTHBeastsAreWeakened) ·
#//           boundary=N/A (no numeric threshold anywhere in the text) ·
#//           control=N/A (the card names no owner-scoped zone; both Beasts are CREATED under their
#//           respective controllers, and there is no take-control interaction to model) ·
#//           reqboundary=SimulateRequestBoundary_BothBeastsAndTheWeaknessSurvive
#//
#// HMW_237 Easy Prey (Event, cost 1, Cunning, Innate, non-unique)
#// "Create a Beast token.
#//  An opponent creates a Beast token. Give a Weakness token to it."
#//
#// ⚠ PREVIEW SET — HMW has no card-specific-rulings.md entry, so two readings below are reasoned from
#// the CR and FLAGGED rather than sourced:
#//   (a) "it" is the OPPONENT's Beast — the one named by the immediately preceding sentence — not yours.
#//       That is what makes the card asymmetric and is the only reading under which the name means
#//       anything: you get a clean 3/3, they get 3/3 with -1/-1.
#//   (b) "An opponent" is a CHOICE (a prompt above two seats), not a loop over all of them.
#//
#// Beast (HMW_T03) is a 3/3 ground Creature token; Weakness (HMW_T02) is a -1/-1 token upgrade.
#// This section is the plain two-seat shape: one Beast each.

## GIVEN
CommonSetup: yyk/bbw/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_237

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_T03
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:HMW_T03

---

# OnlyTheOpponentsBeastIsWeakened
#// ⚠ THE LOAD-BEARING ASYMMETRY, and the reason reading (a) above matters. Both players end with a
#// Beast, so a build that weakened the WRONG one — or both, or neither — still passes the section above.
#// Here the opponent's Beast carries exactly one Weakness token and YOURS carries none.
#// Asserting the upgrade COUNT on both sides is what makes this discriminate in every direction.

## GIVEN
CommonSetup: yyk/bbw/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_237

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:HMW_T02
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# TheWeaknessActuallyShrinksTheirBeast
#// The token is only worth attaching if it does something: Weakness is -1/-1, so the opponent's Beast
#// reads 2/2 while yours stays 3/3. Asserting the STATS rather than only the attachment is what proves
#// the token is live rather than a decoration — and the side-by-side comparison against your own
#// untouched 3/3 removes any doubt about which effect produced the number.

## GIVEN
CommonSetup: yyk/bbw/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_237

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:2
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3

---

# TwoSeats_NoPromptIsRaised
#// PREMIER MUST STAY BYTE-IDENTICAL. With exactly one opponent the "an opponent" choice is degenerate,
#// so SWUQueueChooseOpponent resolves it invisibly (PASSPARAMETER) and the player is never asked a
#// question with one answer. Without this, converting the card to the Twin Suns picker would silently
#// add a prompt to every two-player game.

## GIVEN
CommonSetup: yyk/bbw/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_237

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2NODECISION
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# TwinSuns_PickerOffersEveryOpponentAndNeverYou
#// TWIN SUNS — the OFFER, left pending rather than answered. "An opponent" is a real choice above two
#// seats, so the pool must be every LIVE opponent and must never include the caster.
#// Per the eligibility rules this clause is the "something is done TO them" shape, so NO opponent is
#// filtered out: a free 2/2 is arguably a gift, and filtering on "would they want it" is exactly the
#// mistake that shape exists to prevent.
#// This section cannot pass at two seats — there is only one opponent there and no menu at all.

## GIVEN
CommonSetup: yyk/bbw/{myResources:1}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: HMW_237

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

# TwinSuns_ChosenFarSeatGetsTheWeakenedBeast
#// The answer must be honoured, and only by the seat chosen. Answering P3 — neither the caster, nor
#// OtherPlayer(1), nor the last live seat — means no legacy two-seat code path can produce it by
#// accident. Seat 3 gets the weakened Beast; seats 2 and 4 get nothing at all, and seat 1 keeps its own
#// clean Beast from the first clause.

## GIVEN
CommonSetup: yyk/bbw/{myResources:1}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: HMW_237

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P3

## EXPECT
SEATCOUNT:4
P3GROUNDARENACOUNT:1
P3GROUNDARENAUNIT:0:CARDID:HMW_T03
P3GROUNDARENAUNIT:0:UPGRADECOUNT:1
P3GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENACOUNT:0
P4GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# TwinSuns_AnEliminatedSeatIsNotOffered
#// LIVE seats, not seat order. Seat 2 is eliminated, so the picker must offer only seats 3 and 4 — an
#// eliminated player cannot be made to create anything. This is the cell that separates
#// GetLiveSeatsArray()/OpponentsOf() from a hand-rolled 1..N loop.

## GIVEN
CommonSetup: yyk/bbw/{myResources:1}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 134
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: HMW_237

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1OPTIONHAS:P3
P1OPTIONHAS:P4
P1OPTIONNOT:P2
P1OPTIONNOT:P1

---

# Jerjerrod_DoublesAndBOTHBeastsAreWeakened
#// ⚠ THE RIDER CELL. ASH_094 Moff Jerjerrod — "If you would create a number of tokens, you may defeat
#// this unit. If you do, create twice that number of tokens instead." The doubled tokens are created
#// LATER, inside Jerjerrod's own decision handler, so a Weakness stamped on the UID returned by the
#// first creation would miss the second Beast entirely. Passing the rider to the BATCH api
#// (SWUCreateUnitTokens' $upgradeToken) is what threads it through the doubling.
#//
#// Jerjerrod belongs to the OPPONENT here, because it is the OPPONENT who creates: the offer is theirs
#// to accept. They end with TWO Beasts, BOTH weakened (2/2), and Jerjerrod defeated as the cost.
#// The caster's own single Beast is untouched — its creation happens before any of this and has no
#// rider, so a build that leaked the rider onto the caster's batch fails here too.

## GIVEN
CommonSetup: yyk/bbw/{myResources:1}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: HMW_237
WithP2GroundArena: ASH_094:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:HMW_T03
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:1:CARDID:HMW_T03
P2GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# Jerjerrod_Declined_OneWeakenedBeastOnly
#// The decline partner: refusing Jerjerrod's offer leaves him alive and produces the ordinary single
#// weakened Beast. Without it, "both beasts weakened" above could pass for a build that always doubles.

## GIVEN
CommonSetup: yyk/bbw/{myResources:1}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: HMW_237
WithP2GroundArena: ASH_094:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:NO

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:ASH_094
P2GROUNDARENAUNIT:1:CARDID:HMW_T03
P2GROUNDARENAUNIT:1:UPGRADECOUNT:1

---

# SimulateRequestBoundary_BothBeastsAndTheWeaknessSurvive
#// REQUEST BOUNDARY. The card writes across a decision in the multiplayer form (the opponent pick), so
#// the boundary is placed between the play and the answer — the chosen seat must still be honoured and
#// the rider still applied in the fresh request.

## GIVEN
CommonSetup: yyk/bbw/{myResources:1}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: HMW_237

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:P3

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:CARDID:HMW_T03
P3GROUNDARENAUNIT:0:UPGRADECOUNT:1
P3GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
