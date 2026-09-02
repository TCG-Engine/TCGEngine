# EndorBase_Draws
#// HMW_247 Surveillance Cruiser (Unit, Space, 4/4, cost 4, [Villainy], Imperial/Vehicle/Capital Ship,
#// non-unique) — "When Played: If an opponent controls an Endor, Kashyyyk, Naboo, or Tatooine base,
#// draw a card."
#//
#// COVERAGE: offer=N/A (structural: no target pool and no choice anywhere — a condition and a draw) ·
#//           decline=N/A (structural: no "may", no "up to"; nothing to refuse) ·
#//           boundary=N/A (structural: no threshold or count — one card, one condition. The nearest
#//           thing is the TRAIT SET, covered by four positives plus UnlistedBaseTrait_NoDraw) ·
#//           control=OnlyMyOwnBaseMatches_NoDraw — "an OPPONENT controls" is the whole scoping
#//           question, and a base's controller is the seat that owns it ·
#//           reqboundary=RequestBoundary_ConditionStillReadsAfterTheBoundary ·
#//           modes=2P,TwinSuns,TeamSuns — "an opponent" is a PLAYER REFERENCE, so at 3-4 seats ANY
#//           opponent's base satisfies it (TwinSuns_AFarSeatsBaseCounts), and in a 2v2 a TEAMMATE is not
#//           an opponent (TeamSuns_TeammatesBaseDoesNotCount).
#//
#// FOUR named traits means four positives: a card wired for one of them passes the obvious test and
#// silently ignores the rest. Endor first.
#// ⚠ The deck is seeded in every section — an empty deck turns a draw into base damage (CR 6.1), which
#// would read as the condition misfiring rather than a fixture omission.

## GIVEN
CommonSetup: rrk/rrk/{
  myResources:4;
  theirBase:HMW_023
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_247
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:HMW_247
P1HANDCOUNT:1
P1DECKCOUNT:2
P1BASEDMG:0

---

# KashyyykBase_Draws
#// HMW_247 — second of the four named traits. HMW_021 Kashirho is a Kashyyyk base.

## GIVEN
CommonSetup: rrk/rrk/{
  myResources:4;
  theirBase:HMW_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_247
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:2

---

# NabooBase_Draws
#// HMW_247 — third of the four. HMW_020 Great Grass Plains is a Naboo base.

## GIVEN
CommonSetup: rrk/rrk/{
  myResources:4;
  theirBase:HMW_020
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_247
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:2

---

# TatooineBase_Draws
#// HMW_247 — fourth of the four. HMW_026 is a Tatooine base from this set's blank-text base cycle.

## GIVEN
CommonSetup: rrk/rrk/{
  myResources:4;
  theirBase:HMW_026
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_247
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:2

---

# UnlistedBaseTrait_NoDraw
#// HMW_247 — THE NEGATIVE that proves the trait list is load-bearing. SOR_029 Administrator's Tower
#// carries none of the four named traits, so nothing is drawn: the deck is untouched and the hand is
#// empty after the play. Without this, an implementation that drew unconditionally passes all four
#// positives above.

## GIVEN
CommonSetup: rrk/rrk/{
  myResources:4;
  theirBase:SOR_029
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_247
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:HMW_247
P1HANDCOUNT:0
P1DECKCOUNT:3
P1BASEDMG:0

---

# OnlyMyOwnBaseMatches_NoDraw
#// ⚠ HMW_247 — THE SCOPING NEGATIVE, and the one a naive implementation fails. The shared helper is
#// `_SWUControlsBaseWithTrait($player, $trait)`, which reads ONE seat's base — pass it the CASTER and
#// the card triggers off your OWN base. The text says "if an OPPONENT controls" one.
#// Here P1 holds the Kashyyyk base and the opponent's is an ordinary Administrator's Tower: nothing is
#// drawn. Every other section in this file passes with the self-scoped reading.

## GIVEN
CommonSetup: rrk/rrk/{
  myResources:4;
  myBase:HMW_021;
  theirBase:SOR_029
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_247
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1HANDCOUNT:0
P1DECKCOUNT:3

---

# TwinSuns_AFarSeatsBaseCounts
#// ⚠⚠ HMW_247 — THE SEAT-COUNT CELL, and it CANNOT PASS AT TWO SEATS.
#// "An opponent" is a player reference: at 3-4 seats EVERY opponent's base qualifies, not just the one
#// `OtherPlayer()` happens to name. Here the seat directly opposite (P2) holds an ordinary base and only
#// the FAR seat P3 holds a Naboo base — so a two-seat implementation looks at P2, finds nothing, and
#// does not draw.
#// ⚠ Far-seat bases need WithP3Base explicitly: CommonSetup dresses seats 1-2 only, and that exact
#// omission has already faked a "fan-out is broken" report once on HMW_011.

## GIVEN
CommonSetup: rrk/rrk/{
  myResources:4;
  theirBase:SOR_029
}
SkipPreGame: true
P1OnlyActions: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: HMW_020
WithP4Base: SOR_029
WithP1Hand: HMW_247
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1SPACEARENACOUNT:1
P1HANDCOUNT:1
P1DECKCOUNT:2

---

# TeamSuns_TeammatesBaseDoesNotCount
#// HMW_247 — "an OPPONENT", so in a 2v2 a TEAMMATE's base is not one. Teams are seat parity (1+3 vs
#// 2+4), so P1's partner is P3. Only P3 holds a Kashyyyk base; both actual opponents (P2, P4) hold
#// ordinary bases — nothing is drawn.
#// This is the section that separates OpponentsOf() from "every live seat but me", which are the same
#// set in a free-for-all and different in a team game.

## GIVEN
CommonSetup: rrk/rrk/{
  myResources:4;
  theirBase:SOR_029
}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: HMW_021
WithP4Base: SOR_029
WithP1Hand: HMW_247
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1SPACEARENACOUNT:1
P1HANDCOUNT:0
P1DECKCOUNT:3

---

# RequestBoundary_ConditionStillReadsAfterTheBoundary
#// HMW_247 — the REQUEST-BOUNDARY cell in its no-decision form. The card raises no prompt, but a
#// request ends at every player ACTION, so the play that reads the condition happens in a different
#// process from the action before it. The condition must be recomputed from the live board rather than
#// from anything cached at setup. A cheap filler unit is played first, then the boundary, then the
#// Cruiser — which must still see the opponent's Endor base and draw.

## GIVEN
CommonSetup: rrk/rrk/{
  myResources:6;
  theirBase:HMW_023
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_225
WithP1Hand: HMW_247
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:1:CARDID:HMW_247
P1HANDCOUNT:1
P1DECKCOUNT:2
