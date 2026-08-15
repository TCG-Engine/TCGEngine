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
