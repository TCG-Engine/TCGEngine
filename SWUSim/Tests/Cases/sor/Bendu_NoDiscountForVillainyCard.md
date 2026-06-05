# SOR_056 Bendu — the discount excludes [Villainy] (and [Heroism]) cards. Bendu attacks (arms), then
# P1 plays SOR_225 (a Villainy Space unit, cost 1) → NO discount → full cost 1, so 3 ready resources →
# 2 left. (If the discount wrongly applied, SOR_225 would cost 0 → RESAVAILABLE:3.)

## GIVEN
CommonSetup: bbk/bbk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_056:1:0
WithP1Hand: SOR_225

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1RESAVAILABLE:2
