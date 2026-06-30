# LAW_225 Han's Golden Dice — guard: if the milled card's cost is EVEN, no Credit is created. The top
# card is SOR_046 (cost 4, even) → discarded, no Credit.

## GIVEN
CommonSetup: rrk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_225
WithP1Deck: SOR_046

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1CREDITCOUNT:0
