# DiscountAppliesOnlyOnce
#// SOR_056 Bendu — the discount is a ONE-SHOT "next card" charge, consumed by the first neutral card.
#// Bendu attacks (arms), then P1 plays two JTL_069 (Vigilance neutral, cost 5): the FIRST costs 3, the
#// SECOND costs the full 5. 8 ready resources → 3 + 5 = 0 left, both played. (If the charge weren't
#// consumed, both would cost 3 and leave 2 — RESAVAILABLE:0 is the consume discriminator.)

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

---

# NoDiscountForVillainyCard
#// SOR_056 Bendu — the discount excludes [Villainy] (and [Heroism]) cards. Bendu attacks (arms), then
#// P1 plays SOR_225 (a Villainy Space unit, cost 1) → NO discount → full cost 1, so 3 ready resources →
#// 2 left. (If the discount wrongly applied, SOR_225 would cost 0 → RESAVAILABLE:3.)

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

---

# OnAttack_NextNeutralCardCheaper
#// SOR_056 Bendu (Unit 4/7, Vigilance, Sentinel) — "On Attack: The next non-[Heroism], non-[Villainy]
#// card you play this phase costs 2 less." Bendu attacks the base (arming the discount), then P1 plays
#// JTL_069 (a Vigilance = neutral Space unit, cost 5) → it costs 3, so 7 ready resources → 4 left.

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
