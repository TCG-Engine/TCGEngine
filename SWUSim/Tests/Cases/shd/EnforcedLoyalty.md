# DefeatFriendly_Draw2
#// SHD_108 Enforced Loyalty (2-cost event, Command/Command) — "Defeat a friendly unit. If you do, draw 2
#// cards." With one friendly unit (SEC_080) it auto-resolves: SEC_080 is defeated (to P1's discard, joining
#// the event = 2) and P1 draws 2 from its deck.
#// COVERAGE: offer=Offer_FriendlyUnitsOnly_EnemyNotOffered (two friendly units in two arenas plus an enemy
#//           unit; the decision is left pending so SELECTABLEEXACT is the assertion — "a FRIENDLY unit"
#//           means the enemy is absent from the pool, and the arena is irrelevant) ·
#//           reqboundary=RequestBoundary_ChoiceStillResolves (the pick lands in a later request than the
#//           one that built the offer) · control=N/A (an event has no persistent object; the ability
#//           resolves entirely for the player who played it and there is nothing whose control could
#//           change) · boundary pair=the CR 6.1 empty-deck ladder DefeatFriendly_Draw2 (2 cards -> draw 2,
#//           no base damage) / OneCardDeck_DrawsOneAndTakes3BaseDamage (1 card -> draw 1 + 3) /
#//           EmptyDeck_DrawsNoneAndTakes6BaseDamage (0 cards -> draw 0 + 6) · decline=N/A (the defeat is
#//           mandatory — there is no "you may", so a friendly unit on the board cannot be declined;
#//           NoFriendly_Fizzle is the only no-effect path and it is a MISSING-TARGET fizzle, asserted with
#//           P1NODECISION plus an untouched hand and deck because "if you do" gates the draw too)

## GIVEN
CommonSetup: ggk/ggk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_108
WithP1GroundArena: SEC_080:1:0
WithP1Deck: [SOR_095 SOR_128]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:2
P1DECKCOUNT:0
P1DISCARDCOUNT:2

---

# NoFriendly_Fizzle
#// SHD_108 Enforced Loyalty — with no friendly unit to defeat, the effect fizzles: no defeat, no draw.
#// The event still lands in the discard.

## GIVEN
CommonSetup: ggk/ggk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_108
WithP1Deck: [SOR_095 SOR_128]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:2
P1DISCARDCOUNT:1
P1NODECISION

---

# Offer_FriendlyUnitsOnly_EnemyNotOffered
#// SHD_108 — "a FRIENDLY unit" is the load-bearing half of the target filter. P1 controls SEC_080 on the
#// ground and SOR_237 in space; P2 controls SOR_164 on the ground. The section stops on the pending choice
#// so the OFFER itself is the assertion: exactly the two friendly units are legal, in EITHER arena, and the
#// enemy unit is absent. Two legal options are seeded deliberately so the choice cannot auto-resolve.

## GIVEN
CommonSetup: ggk/ggk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_108
WithP1GroundArena: SEC_080:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_164:1:0
WithP1Deck: [SOR_095 SOR_128]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0
P1GROUNDARENACOUNT:1
P1SPACEARENACOUNT:1
P2GROUNDARENACOUNT:1
P1HANDCOUNT:0
P1DECKCOUNT:2

---

# ChooseSpaceUnit_DefeatedAndDraw2
#// SHD_108 — resolution half of Offer_FriendlyUnitsOnly_EnemyNotOffered. With a legal target in each arena
#// P1 picks the SPACE unit: SOR_237 is defeated (space arena empties, ground SEC_080 is untouched) and the
#// draw 2 follows. The enemy SOR_164 is still on the board, proving the defeat was aimed at a friendly.
#// P1's discard holds the event plus the defeated SOR_237.

## GIVEN
CommonSetup: ggk/ggk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_108
WithP1GroundArena: SEC_080:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_164:1:0
WithP1Deck: [SOR_095 SOR_128]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENACOUNT:1
P1HANDCOUNT:2
P1DECKCOUNT:0
P1DISCARDCOUNT:2
P1NODECISION

---

# OneCardDeck_DrawsOneAndTakes3BaseDamage
#// SHD_108 + CR 6.1 — a draw from an EMPTY deck deals 3 damage to that player's own base instead. With
#// exactly ONE card left, "draw 2 cards" draws the last card and then hits an empty deck for the second
#// draw: hand 1, base damage 3. Middle rung of the empty-deck ladder (DefeatFriendly_Draw2 = 2 cards ->
#// draw 2 + 0 damage; EmptyDeck_DrawsNoneAndTakes6BaseDamage = 0 cards -> draw 0 + 6). The lone friendly
#// unit makes the target choice auto-resolve, so no answer is supplied.

## GIVEN
CommonSetup: ggk/ggk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_108
WithP1GroundArena: SEC_080:1:0
WithP1Deck: [SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1HANDCARD:0:SOR_095
P1DECKCOUNT:0
P1BASEDMG:3
P1DISCARDCOUNT:2
P1NODECISION

---

# EmptyDeck_DrawsNoneAndTakes6BaseDamage
#// SHD_108 + CR 6.1 — bottom rung of the empty-deck ladder. The deck is already empty when the event
#// resolves, so BOTH draws whiff and each deals 3 to P1's own base: hand stays empty and the base takes 6.
#// The defeat still happens (it is not gated on the draw succeeding) — SEC_080 leaves the arena and joins
#// the event in the discard.

## GIVEN
CommonSetup: ggk/ggk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_108
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:0
P1DECKCOUNT:0
P1BASEDMG:6
P1DISCARDCOUNT:2
P1NODECISION

---

# RequestBoundary_ChoiceStillResolves
#// SHD_108 — the target choice is answered in a LATER request than the one that queued it, so the whole
#// "defeat, then draw 2" continuation has to live in serialized state rather than a transient global. Same
#// fixture as ChooseSpaceUnit_DefeatedAndDraw2 with a fresh-process boundary inserted between the play and
#// the pick: SOR_237 is still defeated and the 2 cards are still drawn.

## GIVEN
CommonSetup: ggk/ggk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_108
WithP1GroundArena: SEC_080:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_164:1:0
WithP1Deck: [SOR_095 SOR_128]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:1
P1HANDCOUNT:2
P1DECKCOUNT:0
P1DISCARDCOUNT:2
P1NODECISION
