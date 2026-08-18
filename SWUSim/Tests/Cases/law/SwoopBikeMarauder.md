# OnAttackDraw
#// LAW_107 Swoop Bike Marauder (4/4) — On Attack: draw a card. Attacks the base; draws 1.

## GIVEN
CommonSetup: bbw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_107:1:0
WithP1Deck: SOR_237

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HANDCOUNT:1

---

# EmptyDeckSelfDamage
#// LAW_107 Swoop Bike Marauder — On Attack: draw a card, but with an EMPTY deck the draw fails and the
#// controller's base takes 3 damage instead (CR: drawing from an empty deck deals 3 to your base).

## GIVEN
CommonSetup: bbw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_107:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HANDCOUNT:0
P1BASEDMG:3

---

# DrawFiresOnAUnitAttackToo_AndTakesTheTOPCard
#// LAW_107 Swoop Bike Marauder — "On Attack: Draw a card" is not gated on what is attacked, so it fires on
#// a unit attack as well; and the card drawn must be the TOP of the deck, not an arbitrary one. The 4/4
#// Marauder attacks a 1/1 Battle Droid token (defeating it, taking 1) and draws SOR_237 from a deck whose
#// top is SOR_237 and whose next card is SOR_095. Both existing sections attack the base and neither
#// asserts WHICH card arrived.

## GIVEN
CommonSetup: bbw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_107:1:0
WithP2GroundArena: TWI_T01:1:0
WithP1Deck: [SOR_237 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1HANDCARD:0:SOR_237
P1DECKCOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:1

---

# DeckOfExactlyOne_DrawsItAndLeavesAnEmptyDeck
#// LAW_107 Swoop Bike Marauder — the boundary between OnAttackDraw (cards available) and
#// EmptyDeckSelfDamage (none at all): with exactly ONE card left the draw succeeds, the deck ends empty,
#// and no CR 6.1 empty-deck damage is taken during the action phase — that penalty belongs to the regroup
#// draw, not to running the last card out.

## GIVEN
CommonSetup: bbw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_107:1:0
WithP1Deck: SOR_237

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
P1BASEDMG:0
P2BASEDMG:4
