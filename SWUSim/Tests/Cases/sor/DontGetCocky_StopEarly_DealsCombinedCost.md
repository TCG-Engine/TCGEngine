# SOR_223 Don't Get Cocky (event, cost 4) — choose a unit, reveal cards one at a time until you stop
# (or hit 7), and if the combined cost is ≤7 deal that much to the unit. Here P1 reveals SOR_095 (cost 2)
# then SOR_237 (cost 2) and stops: combined 4 ≤ 7, so the chosen unit (LAW_124, a 4/7) takes 4. The two
# revealed cards go to the bottom of the deck (count stays 3). Cunning is off-aspect for SOR_002 → cost 6.

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
