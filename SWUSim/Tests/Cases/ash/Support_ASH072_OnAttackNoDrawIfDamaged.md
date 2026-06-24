# ASH_072 Doctor Pershing (Ground, 0/4, Support) — On Attack draws ONLY with 3+ remaining HP. Pre-damaged
# to 2 remaining HP (2 damage on 4), Pershing attacks the enemy base and draws NOTHING.
## GIVEN
CommonSetup: bbw/bbk
WithP1GroundArena: ASH_072:1:2
WithP1Deck: SOR_095
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:1
