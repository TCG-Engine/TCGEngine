# BoKatan_WhenDefeated_BoundaryBelow15
#// SHD_157 Bo-Katan Kryze — the threshold is "15 or more". P1's base has 15 (qualifies); P2's base has
#// 14 (does NOT). So SHD_157's defeat draws exactly 1.

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

---

# BoKatan_WhenDefeated_DrawPerDamagedBase
#// SHD_157 Bo-Katan Kryze — "When Defeated: For each player with 15 or more damage on their base, draw a
#// card." Both bases start at 15 damage. SHD_157 (3/3) attacks a Wampa (SOR_164 4/5): deals 3 (survives),
#// counters 4 → SHD_157 dies. Its When Defeated counts both bases (15 ≥ 15) → P1 draws 2.

## GIVEN
CommonSetup: rrk/rrk/{myBaseDamage:15;theirBaseDamage:15}
P1OnlyActions: true
WithP1GroundArena: SHD_157:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1HANDCOUNT:2
