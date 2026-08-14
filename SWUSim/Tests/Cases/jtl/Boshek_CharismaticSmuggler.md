# AsUpgrade_MillReturnOdd
#// JTL_215 BoShek (pilot) — When played as an upgrade: Discard 2 from your deck; return each odd-cost one
#// to hand. Deck top: SOR_225 (cost 1, odd) and SOR_095 (cost 2, even). Odd → hand, even → discard.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1Hand: JTL_215
WithP1SpaceArena: SOR_044:1:0
WithP1Deck: SOR_225
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
P1DISCARDCOUNT:1

---

# AsUpgrade_OddCostCardIsDISCARDEDThenReturned
#// "Discard 2 cards from your deck. RETURN each odd-cost one to your hand" — the odd card is DISCARDED
#// first (its when-discarded triggers fire), then returned. Deck top: LAW_206 That's a Rock (cost 1,
#// odd) + SOR_095 (cost 2, even). The Rock's "when discarded from your hand or deck: you may deal 1"
#// prompts (two units seeded for a real offer), resolves for 1 on the enemy Wampa — and the Rock still
#// ends in hand, with the marine in the discard.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1Hand: JTL_215
WithP1SpaceArena: SOR_044:1:0
WithP2GroundArena: SOR_164:1:0
WithP1Deck: LAW_206
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:LAW_206
P1DECKCOUNT:0
P1DISCARDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:1
