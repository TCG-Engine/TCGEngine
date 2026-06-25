# SEC_017 Sabé (leader front) — "When a friendly unit deals combat damage to a base: You may exhaust this
# leader. If you do, look at the top 2 cards of the defending player's deck. Discard 1 of those cards.
# (Put the other back on top.)" P1's SOR_095 attacks the enemy base; Sabé exhausts → discard SOR_046 from
# the top 2 (SOR_046, SOR_128), leaving SOR_128 on top.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:SEC_017;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2Deck: [SOR_046 SOR_128]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:SOR_046

## EXPECT
P2BASEDMG:3
P1LEADER:EXHAUSTED
P2DISCARDCOUNT:1
P2DECKCOUNT:1
P2DECKTOPCARD:SOR_128
