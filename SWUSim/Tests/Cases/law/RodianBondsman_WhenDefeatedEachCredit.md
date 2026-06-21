# LAW_116 Rodian Bondsman (2/3) — When Defeated: each player creates a Credit token. Attacks SOR_046
# (3/7) and dies; both players gain a Credit.

## GIVEN
CommonSetup: bbw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_116:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1CREDITCOUNT:1
P2CREDITCOUNT:1
