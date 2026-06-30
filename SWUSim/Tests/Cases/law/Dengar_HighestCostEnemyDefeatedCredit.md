# LAW_053 Dengar (4/3) — When a unit with the highest cost among enemy units is defeated: create a
# Credit token (once each round). P1's SOR_046 attacks and kills SEC_080 (cost 2, the highest enemy
# cost — SOR_128 is cost 1), so Dengar's controller gets a Credit.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_053:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:1:0

## EXPECT
P1CREDITCOUNT:1
