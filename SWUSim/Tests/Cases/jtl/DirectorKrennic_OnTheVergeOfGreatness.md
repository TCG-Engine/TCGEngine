# FirstWhenDefeatedUnit_CostsOneLess
#// JTL_032 Director Krennic — The first unit you play each round that has a When Defeated ability costs 1
#// resource less. With Krennic in play, JTL_033 (Onyx Squadron Brute, cost 2, has a When Defeated
#// ability) is played for 1, leaving 1 of 2 resources.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_032:1:0
WithP1Hand: JTL_033
WithP1Resources: 2

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_033
P1RESAVAILABLE:1

---

# SecondWhenDefeatedUnit_FullCost
#// JTL_032 Director Krennic — only the FIRST such unit each round gets the discount. Playing two When
#// Defeated units (each cost 2): the first costs 1, the second costs the full 2, so 3 resources are
#// exactly consumed.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_032:1:0
WithP1Hand: JTL_033
WithP1Hand: JTL_033
WithP1Resources: 3

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:2
P1RESAVAILABLE:0

---

# FirstWDUnitConsumedBeforeKrennic
#// JTL_032 Director Krennic — "the FIRST unit you play each round with a When Defeated ability costs 1
#// less" is a PER-PLAYER, per-round slot consumed by ANY such play. P1 plays JTL_033 (a When-Defeated
#// unit) BEFORE Krennic is in play (so no discount), then plays Krennic, then plays a second JTL_033.
#// Because the first When-Defeated unit already consumed the round's slot, the second is NOT discounted —
#// all three cards are paid at full cost (2 + 2 + 2 = 6), leaving 0 resources.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: JTL_033 JTL_032 JTL_033

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:0

---

# DoesNotDiscountUnitWithoutWhenDefeated
#// JTL_032 Director Krennic discounts only units that HAVE a When Defeated ability. SEC_080 (Imperial Dark
#// Trooper, cost 3, no When Defeated) is not discounted: with only 2 resources it cannot be played and
#// stays in hand.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:JTL_001;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_032:1:0
WithP1Hand: SEC_080
WithP1Resources: 2

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_032
P1HANDCOUNT:1
P1RESAVAILABLE:2

---

# DoesNotDiscountOpponentUnits
#// JTL_032 Director Krennic discounts only ITS CONTROLLER's units. P2 plays JTL_033 (Onyx Squadron Brute,
#// cost 2, has a When Defeated) while P1 controls Krennic; P2's cost is NOT reduced, so with only 1 resource
#// P2 cannot play it and it stays in P2's hand.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:JTL_001;myBase:JTL_019;theirBase:SOR_021;theirResources:1}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: JTL_032:1:0
WithP2Hand: JTL_033

## WHEN
- P2>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:0
P2HANDCOUNT:1
P2RESAVAILABLE:1
