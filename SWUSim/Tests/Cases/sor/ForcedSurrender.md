# DamagedBaseOpponentDiscards
#// SOR_175 Forced Surrender — "Draw 2 cards. Each opponent whose base you've damaged this phase discards 2 cards from
#// their hand." P1's SEC_080 attacks P2's base (3 dmg) → P1 has damaged P2's base this phase. P1 then
#// plays SOR_175: draws 2, and P2 (base-damaged) discards both cards from hand.

## GIVEN
CommonSetup: rrk/ggw/{myResources:7;handCardIds:SOR_175;theirHandCardIds:SOR_128,SOR_225}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Deck: SOR_128
WithP1Deck: SOR_225

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P2BASEDMG:3
P1HANDCOUNT:2
P1DECKCOUNT:0
P2HANDCOUNT:0
P2DISCARDCOUNT:2

---

# NoBaseDamageNoDiscard
#// SOR_175 — gating guard: if you did NOT damage an opponent's base this phase, they do NOT discard.
#// P1 plays SOR_175 with no prior base damage → draws 2, but P2's hand is untouched.

## GIVEN
CommonSetup: rrk/ggw/{myResources:7;handCardIds:SOR_175;theirHandCardIds:SOR_128,SOR_225}
P1OnlyActions: true
WithP1Deck: SOR_128
WithP1Deck: SOR_225

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:2
P1DECKCOUNT:0
P2HANDCOUNT:2
P2DISCARDCOUNT:0

---

# SimulateRequestBoundary_OpponentDiscardChoice
#// SOR_175 Forced Surrender — with more than 2 cards in hand the opponent CHOOSES which to discard, so
#// the effect crosses a request boundary in production: P1 plays the event in one request and P2's two
#// Choose_card_to_discard answers arrive in later, FRESH processes. The "P1 damaged P2's base this
#// phase" gate and the remaining-discard count must therefore live in the serialized gamestate, not in
#// memory. Mirrors DamagedBaseOpponentDiscards (P2 base damaged for 3, P1 draws 2, P2 discards 2) with
#// P2's hand widened to 4 so the pick stays interactive and the boundary inserted before P2's answer.
#// NOTE: hand mzIDs are positionally STABLE across the two picks — after myHand-0 is discarded the
#// second offer is myHand-1..3, not a re-indexed 0..2 (same with or without the boundary).

## GIVEN
CommonSetup: rrk/ggw/{myResources:7;handCardIds:SOR_175;theirHandCardIds:SOR_128,SOR_225,SOR_095,SOR_063}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Deck: SOR_128
WithP1Deck: SOR_225

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P2>SimulateRequestBoundary
- P2>AnswerDecision:myHand-0
- P2>AnswerDecision:myHand-1

## EXPECT
P2BASEDMG:3
P1HANDCOUNT:2
P1DECKCOUNT:0
P2HANDCOUNT:2
P2DISCARDCOUNT:2

---

# OverwhelmExcessCountsAsBaseDamage
#// SOR_175 Forced Surrender — "each opponent whose base YOU'VE DAMAGED this phase" does not say HOW, so
#// every route to the opponent's base arms the gate. Here the base damage arrives INDIRECTLY, as
#// Overwhelm excess: P1's Wampa (4/5, Overwhelm) attacks a 3/1 Death Star Stormtrooper, 1 damage is
#// lethal and the other 3 spill onto P2's base. Forced Surrender then draws 2 and empties P2's hand.
#// P2's discard therefore ends with THREE entries: the defeated Stormtrooper (from PLAY, index 0) plus
#// the two forced hand discards (FROM HAND, indexes 1-2).
#// Boundary partner of NoBaseDamageNoDiscard (0 base damage → no discard).
#// COVERAGE: offer=N/A (no target choice on P1's side — the drawer and the discarding opponents are
#//           both determined, and the opponent's own pick is asserted in
#//           SimulateRequestBoundary_OpponentDiscardChoice) · decline=N/A (a played event's effect is
#//           mandatory; the opponent cannot refuse a forced discard) ·
#//           boundary=OneCardHand_DiscardsOnlyWhatIsThere (hand 1 → discards 1, not 2) vs
#//           DamagedBaseOpponentDiscards (hand 2 → discards 2) ·
#//           control=N/A (the event reads a phase-scoped "I damaged that base" marker on the SEAT, and
#//           moves cards between that seat's own hand and discard; no unit and no zone word that a
#//           control change could re-seat) · reqboundary=SimulateRequestBoundary_OpponentDiscardChoice

## GIVEN
CommonSetup: rrk/ggw/{myResources:7;handCardIds:SOR_175;theirHandCardIds:SOR_128,SOR_225}
P1OnlyActions: true
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Deck: SOR_128
WithP1Deck: SOR_225

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:3
P1HANDCOUNT:2
P1DECKCOUNT:0
P2HANDCOUNT:0
P2DISCARDCOUNT:3
P2DISCARDUNIT:1:FROM:HAND
P2DISCARDUNIT:2:FROM:HAND

---

# AbilityDamageCountsAsBaseDamage
#// SOR_175 Forced Surrender — the third funnel to an enemy base: a card ABILITY rather than combat.
#// P1 plays Ruthless Raider (SOR_134), whose When Played deals 2 damage to an enemy base and 2 to an
#// enemy unit; the base damage arms the gate exactly as an attack would, so the subsequent Forced
#// Surrender empties P2's hand. Combat / Overwhelm-excess / ability are separate code paths in the
#// engine, so each needs its own section.

## GIVEN
CommonSetup: rrk/ggw/{myResources:13;theirHandCardIds:SOR_128,SOR_225}
P1OnlyActions: true
WithP1Hand: [SOR_134 SOR_175]
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_128
WithP1Deck: SOR_225

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P2BASEDMG:2
P2GROUNDARENAUNIT:0:DAMAGE:2
P1HANDCOUNT:2
P1DECKCOUNT:0
P2HANDCOUNT:0
P2DISCARDCOUNT:2

---

# OpponentDamagedMyBase_TheyStillDoNotDiscard
#// SOR_175 Forced Surrender — the gate is DIRECTIONAL: "each opponent whose base YOU'VE damaged". Base
#// damage in the other direction does not arm it. P1 passes, P2's Imperial Dark Trooper attacks P1's
#// base for 3, and P1 then plays Forced Surrender: P1 still draws 2, but P2's base is undamaged so P2
#// keeps their whole hand. Distinct from NoBaseDamageNoDiscard, where no base was damaged at all.

## GIVEN
CommonSetup: rrk/ggw/{myResources:7;handCardIds:SOR_175;theirHandCardIds:SOR_128,SOR_225}
WithP2GroundArena: SEC_080:1:0
WithP1Deck: SOR_128
WithP1Deck: SOR_225

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1BASEDMG:3
P2BASEDMG:0
P1HANDCOUNT:2
P1DECKCOUNT:0
P2HANDCOUNT:2
P2DISCARDCOUNT:0

---

# OneCardHand_DiscardsOnlyWhatIsThere
#// SOR_175 Forced Surrender — quantity boundary on the discard clause: "discards 2 cards from their
#// hand" is capped by what the opponent actually holds. Same armed board as
#// DamagedBaseOpponentDiscards but P2 holds a single card: they discard that one card and the effect
#// stops, with no error and no pending decision. (Hand 2 → 2 discarded is the N side; hand 1 → 1
#// discarded is the N-1 side.)

## GIVEN
CommonSetup: rrk/ggw/{myResources:7;handCardIds:SOR_175;theirHandCardIds:SOR_128}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Deck: SOR_128
WithP1Deck: SOR_225

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P2BASEDMG:3
P1HANDCOUNT:2
P2HANDCOUNT:0
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_128
P2DISCARDUNIT:0:FROM:HAND
P1NODECISION
P2NODECISION
