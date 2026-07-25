# EnemyExhausted_Raid
#// LOF_212 Life Wind Sage (3/5) — "While an enemy unit is exhausted, this unit gains Raid 2." With an
#// exhausted enemy in play, attacking the base deals 3 + 2 (Raid) = 5.

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_212:1:0
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5

---

# NoEnemyExhausted_NoRaid
#// LOF_212 Life Wind Sage — negative: with no EXHAUSTED enemy unit it lacks Raid 2. A friendly exhausted
#// unit does not count — only enemy exhaustion matters — so attacking the base deals just 3.

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_212:1:0
WithP1GroundArena: SOR_046:0:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
