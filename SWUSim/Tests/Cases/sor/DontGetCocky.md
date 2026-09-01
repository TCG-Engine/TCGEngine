# Bust_NoDamage
#// COVERAGE: offer=ChoosePool_AnyUnit (exact unit pool asserted pending: friendly AND enemy, both
#//           arenas) + FriendlyUnit_TakesDamage (friendly pick resolves) · decline=StopEarly_DealsCombinedCost
#//           + Bust_NoDamage (NO = "stop revealing") · boundary=SevenCardCap_AutoStops (exactly 7 cost /
#//           7 cards → deals) + Bust_NoDamage (8 > 7 → nothing) + EmptyDeck_DoesNothing (0 cards) ·
#//           reqboundary=SevenCardCap_AutoStops (the reveal loop's running total crosses six answered
#//           requests) · control=ControlledEnemyOwnedUnit_IsChosenAndDamaged — the event itself is a
#//           one-shot with no persistent object to change hands, so the reachable reading is its
#//           TARGET's: a P2-owned unit under P1's control is in the "choose a unit" pool, is addressed
#//           in the controller's frame (myGroundArena-0, and an out-of-pool answer would be rejected),
#//           and takes the damage on P1's board
#// SOR_223 Don't Get Cocky — if the combined cost exceeds 7 you "bust" and deal NOTHING. P1 reveals
#// SOR_043 (cost 8) and stops: 8 > 7, so the chosen unit takes 0. The revealed card returns to the deck.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_223
WithP1Resources: 6
WithP1Deck: SOR_043
WithP1Deck: SOR_095
WithP1Deck: SOR_237
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1DECKCOUNT:3
P1DISCARDCOUNT:1

---

# DeckEmpties_AutoStops
#// SOR_223 Don't Get Cocky — the reveal loop also stops automatically when the deck runs empty. The deck
#// has exactly 2 cards (SOR_095 cost 2, SOR_237 cost 2); after revealing both, the deck is empty so no
#// further prompt is shown — combined 4 ≤ 7 deals 4, and both revealed cards return to the deck (count 2).

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_223
WithP1Resources: 6
WithP1Deck: SOR_095
WithP1Deck: SOR_237
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P1DECKCOUNT:2
P1DISCARDCOUNT:1
P1NODECISION

---

# SevenCardCap_AutoStops
#// SOR_223 Don't Get Cocky — the reveal loop hard-stops after 7 cards (no prompt for an 8th). The deck
#// has 8 cost-1 cards (SOR_251); P1 answers YES six times, the 7th reveal auto-stops, combined cost = 7
#// (≤ 7) so the chosen unit (LAW_124, 7 HP) takes 7 and is defeated. The 7 revealed cards return to the
#// deck (count stays 8).

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_223
WithP1Resources: 6
WithP1Deck: SOR_251
WithP1Deck: SOR_251
WithP1Deck: SOR_251
WithP1Deck: SOR_251
WithP1Deck: SOR_251
WithP1Deck: SOR_251
WithP1Deck: SOR_251
WithP1Deck: SOR_251
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1DECKCOUNT:8
P1DISCARDCOUNT:1
P1NODECISION

---

# StopEarly_DealsCombinedCost
#// SOR_223 Don't Get Cocky (event, cost 4) — choose a unit, reveal cards one at a time until you stop
#// (or hit 7), and if the combined cost is ≤7 deal that much to the unit. Here P1 reveals SOR_095 (cost 2)
#// then SOR_237 (cost 2) and stops: combined 4 ≤ 7, so the chosen unit (LAW_124, a 4/7) takes 4. The two
#// revealed cards go to the bottom of the deck (count stays 3). Cunning is off-aspect for SOR_002 → cost 6.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_223
WithP1Resources: 6
WithP1Deck: SOR_095
WithP1Deck: SOR_237
WithP1Deck: SOR_063
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:NO

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P1DECKCOUNT:3
P1DISCARDCOUNT:1

---

# EmptyDeck_DoesNothing
#// SOR_223 Don't Get Cocky — with NO cards in the deck there is nothing to reveal: the chosen unit
#// (LAW_124, auto-selected as the only unit in play) takes 0 damage and the event just finishes
#// (cost still paid, event discarded, no pending decision).

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_223
WithP1Resources: 6
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1DECKCOUNT:0
P1DISCARDCOUNT:1
P1NODECISION

---

# ChoosePool_AnyUnit
#// SOR_223 Don't Get Cocky — "Choose a unit" is ANY unit: friendly and enemy, ground and space. With
#// P1's Battlefield Marine plus P2's LAW_124 (ground) and Alliance X-Wing (space) in play, playing the
#// event leaves the unit choice PENDING and the pool is exactly those three units. (No answer is given
#// — the offer itself is the assertion; nothing has been revealed yet, so the deck is untouched.)

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_223
WithP1Resources: 6
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Deck: [SOR_095 SOR_063]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0&theirSpaceArena-0
P1DECKCOUNT:2

---

# FriendlyUnit_TakesDamage
#// SOR_223 Don't Get Cocky — the chosen unit may be YOUR OWN. P1 picks its Battlefield Marine (3/3),
#// the first card reveals automatically (SOR_095, cost 2), P1 stops: combined 2 ≤ 7, so the Marine
#// takes 2 and survives at 1 HP; the enemy unit is untouched and the revealed card goes to the bottom
#// of the deck (count stays 2).

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_223
WithP1Resources: 6
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Deck: [SOR_095 SOR_063]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P1DECKCOUNT:2
P1DISCARDCOUNT:1

---

# ControlledEnemyOwnedUnit_IsChosenAndDamaged
#// SOR_223 Don't Get Cocky — the CONTROL axis. "Choose a unit" names neither a controller nor an
#// owner, so a unit P1 CONTROLS but P2 OWNS (the end state after a take-control effect) has to be in
#// the pool, and it has to be addressed in the DECIDING player's frame — myGroundArena-0, not
#// theirGroundArena-0 — because the pool is built from control, not ownership. Out-of-pool answers
#// are rejected, so the answer being accepted is itself the proof of membership. The reveal then
#// stops after one card (SOR_095, cost 2) and the 2 damage lands on that foreign-owned unit sitting
#// on P1's board (3/3 → 2 damage, survives) while P2's own unit is untouched.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_223
WithP1Resources: 6
WithP1GroundArenaControlled: SOR_095:2
WithP2GroundArena: LAW_124:1:0
WithP1Deck: [SOR_095 SOR_063]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P1DECKCOUNT:2
P1DISCARDCOUNT:1
