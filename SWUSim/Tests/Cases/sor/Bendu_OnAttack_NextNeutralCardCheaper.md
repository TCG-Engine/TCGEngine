# SOR_056 Bendu (Unit 4/7, Vigilance, Sentinel) — "On Attack: The next non-[Heroism], non-[Villainy]
# card you play this phase costs 2 less." Bendu attacks the base (arming the discount), then P1 plays
# JTL_069 (a Vigilance = neutral Space unit, cost 5) → it costs 3, so 7 ready resources → 4 left.

## GIVEN
CommonSetup: bbk/bbk/{myResources:7}
P1OnlyActions: true
WithP1GroundArena: SOR_056:1:0
WithP1Hand: JTL_069

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P2BASEDMG:4
P1SPACEARENACOUNT:1
P1RESAVAILABLE:4
