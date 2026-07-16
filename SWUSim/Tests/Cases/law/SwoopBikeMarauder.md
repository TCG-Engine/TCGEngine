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
