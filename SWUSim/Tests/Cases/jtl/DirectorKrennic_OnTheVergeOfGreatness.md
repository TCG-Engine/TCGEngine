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

---

# ResetsWithPhase
#// JTL_032 Director Krennic — the per-round discount slot REFRESHES each round. P1 plays JTL_033 (a When
#// Defeated unit, cost 2 → discounted to 1). The round is advanced (both pass, then decline the regroup
#// resource step). In the NEW round the slot is fresh, so a second JTL_033 is again discounted to 1.
#// Resources start at 12 and are readied at regroup, so only the two 1-cost plays are spent → 11 remain.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:JTL_001;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_032:1:0
WithP1Hand: JTL_033
WithP1Hand: JTL_033
WithP1Resources: 12
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:2
P1RESAVAILABLE:11

---

# DoesNotCountUpgradeWithWhenDefeated
#// JTL_032 Director Krennic — the discount targets the first UNIT with a When Defeated ability; UPGRADES
#// with a When Defeated ability do NOT consume the slot. TWI_069 Roger Roger (upgrade, cost 1, has a When
#// Defeated ability) is played onto Krennic, then JTL_033 (When Defeated unit, cost 2) is still discounted
#// to 1. Roger Roger (1) + discounted JTL_033 (1) = 2 spent of 3 → 1 remains. Had the upgrade wrongly
#// consumed the slot, JTL_033 would cost 2 and 0 would remain.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:JTL_001;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_032:1:0
WithP1Hand: TWI_069
WithP1Hand: JTL_033
WithP1Resources: 3

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_032
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_033
P1RESAVAILABLE:1

---

# MultiTriggerIncludingWhenDefeated
#// JTL_032 Director Krennic — a unit whose single ability lists MULTIPLE trigger types that INCLUDE
#// "When Defeated" still qualifies. SEC_119 Crucible (cost 6) has "When Played/When Defeated: Give an
#// Experience token to each other friendly unit" → discounted to 5. With 6 resources, 1 remains.
#// (Command aspects avoid an off-aspect penalty; Crucible's When Played auto-gives Krennic experience.)

## GIVEN
CommonSetup: ggk/ggk/{theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_032:1:0
WithP1Hand: SEC_119
WithP1Resources: 6

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SEC_119
P1RESAVAILABLE:1

---

# NoRediscountAfterReturnedToHand
#// JTL_032 Director Krennic — the round's discount slot is consumed by the FIRST When Defeated unit played
#// and does not refresh if that same card bounces and is replayed the same round. P1 plays JTL_033
#// (discounted to 1). P2 returns it to P1's hand with TWI_226 Waylay. P1 replays JTL_033 at FULL cost 2.
#// Total P1 spend 1 + 2 = 3 of 6 → 3 remain.

## GIVEN
CommonSetup: bbk/yyk/{myLeader:JTL_001;myBase:JTL_019}
SkipPreGame: true
WithP1GroundArena: JTL_032:1:0
WithP1Hand: JTL_033
WithP2Hand: TWI_226
WithP1Resources: 6
WithP2Resources: 3

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P2>ChooseTheirSpaceUnit:0
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_033
P1RESAVAILABLE:3

---

# NoRediscountFromDiscard
#// JTL_032 Director Krennic — the consumed slot does not refresh for a card replayed from discard the same
#// round. P1 plays SEC_119 Crucible (When Played/When Defeated unit, cost 6 → discounted to 5). P2 defeats
#// it with TWI_077 Vanquish. P1 replays Crucible from discard with JTL_121 Salvage (play a Vehicle unit
#// from discard paying its cost) at FULL cost 6. P1 spend 5 + 0 (Salvage) + 6 = 11 of 12 → 1 remains.

## GIVEN
CommonSetup: ggk/bbk/{}
SkipPreGame: true
WithP1GroundArena: JTL_032:1:0
WithP1Hand: SEC_119
WithP1Hand: JTL_121
WithP2Hand: TWI_077
WithP1Resources: 12
WithP2Resources: 5

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P2>ChooseTheirSpaceUnit:0
- P1>Drain
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SEC_119
P1RESAVAILABLE:1
