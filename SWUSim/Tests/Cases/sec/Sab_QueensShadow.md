# Deployed_BaseHit_DiscardOppHandDraw
#// SEC_017 Sabé (deployed) — Raid 1 + "When this unit deals combat damage to a base: Look at the defending
#// player's hand. You may discard a card from it. If you do, that player draws a card."
#// Deployed Sabé (3/6, Raid 1) attacks the enemy base for 3 + 1 = 4, then discards P2's only hand card;
#// P2 then draws a card (hand back to 1, discard +1).

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

---

# LeaderReaction_LookTop2Discard1
#// SEC_017 Sabé (leader front) — "When a friendly unit deals combat damage to a base: You may exhaust this
#// leader. If you do, look at the top 2 cards of the defending player's deck. Discard 1 of those cards.
#// (Put the other back on top.)" P1's SOR_095 attacks the enemy base; Sabé exhausts → discard SOR_046 from
#// the top 2 (SOR_046, SOR_128), leaving SOR_128 on top.

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
