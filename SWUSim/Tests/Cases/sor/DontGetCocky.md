# Bust_NoDamage
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
