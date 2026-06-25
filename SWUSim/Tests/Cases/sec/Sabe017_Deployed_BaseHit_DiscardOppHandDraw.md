# SEC_017 Sabé (deployed) — Raid 1 + "When this unit deals combat damage to a base: Look at the defending
# player's hand. You may discard a card from it. If you do, that player draws a card."
# Deployed Sabé (3/6, Raid 1) attacks the enemy base for 3 + 1 = 4, then discards P2's only hand card;
# P2 then draws a card (hand back to 1, discard +1).

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:SEC_017:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP2Hand: SOR_095
WithP2Deck: [SOR_128]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirHand-0

## EXPECT
P2BASEDMG:4
P2DISCARDCOUNT:1
P2HANDCOUNT:1
P2DECKCOUNT:0
