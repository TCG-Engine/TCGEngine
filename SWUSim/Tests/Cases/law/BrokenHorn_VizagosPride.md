# WhenPlayedDrawAndResource
#// LAW_083 Broken Horn (5/4) — When Played: if you have fewer cards in hand than an opponent, draw a
#// card; if you control fewer resources than an opponent, resource the top card of your deck. P1 ends
#// with fewer of both (hand 0 vs 3, resources 5 vs 6) -> draw 1 AND resource 1.

## GIVEN
CommonSetup: ryk/bgw/{myResources:5;theirResources:6}
WithP2Hand: SOR_095
WithP2Hand: SOR_237
WithP2Hand: SEC_080
WithP1Deck: SOR_237
WithP1Deck: SOR_095
WithP1Hand: LAW_083

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1RESCOUNT:6
P1DECKCOUNT:0

---

# EmptyDeckDrawDealsBaseDamage
#// LAW_083 Broken Horn — with fewer cards in hand than the opponent the draw fires, but the deck is
#// empty so drawing from it deals 3 damage to P1's base. Fewer resources too, but the resource step
#// also finds an empty deck and does nothing.

## GIVEN
CommonSetup: ryk/bgw/{myResources:5;theirResources:6}
WithP2Hand: SOR_095
WithP2Hand: SOR_237
WithP2Hand: SEC_080
WithP1Hand: SOR_095
WithP1Hand: LAW_083

## WHEN
- P1>PlayHand:1

## EXPECT
P1HANDCOUNT:1
P1RESCOUNT:5
P1DECKCOUNT:0
P1BASEDMG:3

---

# OneCardDeckDrawThenNoResource
#// LAW_083 Broken Horn — fewer hand and fewer resources, deck has exactly one card. The draw takes that
#// card, emptying the deck; the resource step then finds nothing to resource.

## GIVEN
CommonSetup: ryk/bgw/{myResources:5;theirResources:6}
WithP2Hand: SOR_095
WithP2Hand: SOR_237
WithP2Hand: SEC_080
WithP1Deck: SOR_237
WithP1Hand: SOR_095
WithP1Hand: LAW_083

## WHEN
- P1>PlayHand:1

## EXPECT
P1HANDCOUNT:2
P1RESCOUNT:5
P1DECKCOUNT:0
P1BASEDMG:0

---

# NotFewerHandNoDrawButResource
#// LAW_083 Broken Horn — P1 does NOT have fewer cards in hand after playing it (2 vs 2), so no draw;
#// but P1 controls fewer resources, so the top card of the deck is resourced.

## GIVEN
CommonSetup: ryk/bgw/{myResources:5;theirResources:6}
WithP2Hand: SOR_095
WithP2Hand: SOR_237
WithP1Deck: SOR_237
WithP1Hand: SOR_095
WithP1Hand: SEC_080
WithP1Hand: LAW_083

## WHEN
- P1>PlayHand:2

## EXPECT
P1HANDCOUNT:2
P1RESCOUNT:6
P1DECKCOUNT:0
P1BASEDMG:0

---

# FewerHandDrawButEqualResourcesNoResource
#// LAW_083 Broken Horn — fewer cards in hand than the opponent, so draw; resources are EQUAL (not
#// fewer), so no resource. Deck of two cards loses one to the draw.

## GIVEN
CommonSetup: ryk/bgw/{myResources:5;theirResources:5}
WithP2Hand: SOR_095
WithP2Hand: SOR_237
WithP1Deck: SOR_237
WithP1Deck: SEC_080
WithP1Hand: SOR_095
WithP1Hand: LAW_083

## WHEN
- P1>PlayHand:1

## EXPECT
P1HANDCOUNT:2
P1RESCOUNT:5
P1DECKCOUNT:1
P1BASEDMG:0
