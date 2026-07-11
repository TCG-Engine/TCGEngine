# SHD_157 Bo-Katan Kryze — the threshold is "15 or more". P1's base has 15 (qualifies); P2's base has
# 14 (does NOT). So SHD_157's defeat draws exactly 1.

## GIVEN
CommonSetup: rrk/rrk/{myBaseDamage:15;theirBaseDamage:14}
P1OnlyActions: true
WithP1GroundArena: SHD_157:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1HANDCOUNT:1
