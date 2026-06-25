# SOR_223 Don't Get Cocky — the reveal loop hard-stops after 7 cards (no prompt for an 8th). The deck
# has 8 cost-1 cards (SOR_251); P1 answers YES six times, the 7th reveal auto-stops, combined cost = 7
# (≤ 7) so the chosen unit (LAW_124, 7 HP) takes 7 and is defeated. The 7 revealed cards return to the
# deck (count stays 8).

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
