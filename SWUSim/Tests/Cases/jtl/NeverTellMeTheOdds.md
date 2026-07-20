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

---

# FewerThanThreeCards_MillWhatsLeft
#// JTL_208 Never Tell Me the Odds — discards up to 3 from each deck, or as many as remain. P1's deck has 2
#// cards (SOR_128 cost 1 = odd, SOR_095 cost 2), P2's deck has 1 (SOR_144 Red Three cost 3 = odd). Both
#// decks empty out; two odd-cost cards were discarded → deal 2 to a unit (the AT-ST survives with 2 damage).

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
WithP2GroundArena: SOR_232:1:0
WithP1Deck: SOR_128
WithP1Deck: SOR_095
WithP2Deck: SOR_144

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1DECKCOUNT:0
P2DECKCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# BothDecksEmpty_DoesNothing
#// JTL_208 Never Tell Me the Odds — with both decks empty, nothing is discarded, no odd-cost cards, and no
#// damage is dealt (the event just pays its cost and goes to discard).

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
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:0
P2BASEDMG:0
P1NODECISION
