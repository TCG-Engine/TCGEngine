# MillSix_DamageByOddCost
#// JTL_208 — Discard 3 from an opponent's deck and 3 from your deck; deal damage to a unit equal to the
#// number of odd-cost cards discarded. Self: SOR_128(1,odd)/SOR_095(2)/SOR_237(2). Opp: SOR_225(1,odd)/
#// SOR_237(2)/SOR_044(2). Two odd-cost → deal 2 to the only unit (SOR_046, 7 HP).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_208
WithP1Resources: 7
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_128
WithP1Deck: SOR_095
WithP1Deck: SOR_237
WithP2Deck: SOR_225
WithP2Deck: SOR_237
WithP2Deck: SOR_044

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1DECKCOUNT:0
P2DECKCOUNT:0
