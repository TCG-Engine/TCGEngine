# Ruthlessness_DefeatDealsBaseDamage
#// SHD_143 Ruthlessness — Upgrade, cost 1, [Villainy][Aggression], trait Innate, +2/+0. Attached unit
#// gains: "When this unit attacks and defeats a unit: Deal 2 damage to the defending player's base."
#// COVERAGE: offer=N/A (no target pick — the damage always goes to the defending player's base) ·
#//           request boundary=N/A (the whole grant resolves inside the attack with no decision) ·
#//           control=N/A for a seat change; the "whose base" half is pinned by every section asserting
#//           P2BASEDMG while P1 owns the host ·
#//           boundary pair=Ruthlessness_DefeatDealsBaseDamage (defender dies → 2) +
#//           Ruthlessness_NoDefeat_NoBaseDamage (defender survives → 0) ·
#//           decline=N/A (mandatory, no "you may").
#// Host (SOR_046 3/7 + SHD_143 +2/+0 = 5 power) attacks and defeats SHD_095 (2/3); the grant then deals
#// 2 to P2's base.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_143
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:2

---

# Ruthlessness_NoDefeat_NoBaseDamage
#// SHD_143 Ruthlessness — the base damage is gated on DEFEATING the defender. Host (5 power) attacks a
#// SOR_046 (7 HP) that survives → no defeat, so no base damage.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_143
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P2BASEDMG:0
