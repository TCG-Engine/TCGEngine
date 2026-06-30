# ASH_072 Doctor Pershing (Ground, 0/4, Support) — On Attack: if this unit has 3 or more remaining HP,
# draw a card. Undamaged (4 HP ≥ 3), Pershing attacks the enemy base and draws a card.
## GIVEN
CommonSetup: bbw/bbk
WithP1GroundArena: ASH_072:1:0
WithP1Deck: SOR_095
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
