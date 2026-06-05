# SOR_056 Bendu — the discount is a ONE-SHOT "next card" charge, consumed by the first neutral card.
# Bendu attacks (arms), then P1 plays two JTL_069 (Vigilance neutral, cost 5): the FIRST costs 3, the
# SECOND costs the full 5. 8 ready resources → 3 + 5 = 0 left, both played. (If the charge weren't
# consumed, both would cost 3 and leave 2 — RESAVAILABLE:0 is the consume discriminator.)

## GIVEN
CommonSetup: bbk/bbk/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: SOR_056:1:0
WithP1Hand: JTL_069
WithP1Hand: JTL_069

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:2
P1RESAVAILABLE:0
