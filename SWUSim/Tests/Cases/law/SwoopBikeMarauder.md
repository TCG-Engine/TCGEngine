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
