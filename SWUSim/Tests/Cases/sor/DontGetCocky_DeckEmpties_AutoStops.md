# SOR_223 Don't Get Cocky — the reveal loop also stops automatically when the deck runs empty. The deck
# has exactly 2 cards (SOR_095 cost 2, SOR_237 cost 2); after revealing both, the deck is empty so no
# further prompt is shown — combined 4 ≤ 7 deals 4, and both revealed cards return to the deck (count 2).

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
