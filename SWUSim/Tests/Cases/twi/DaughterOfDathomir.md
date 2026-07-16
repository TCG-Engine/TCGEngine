# NoRestore_WhileDamaged
#// TWI_062 Daughter of Dathomir — while DAMAGED (1 damage on her) she does NOT have Restore 2, so
#// attacking heals nothing from P1's base (stays at 5).

## GIVEN
CommonSetup: bbk/grw/{myResources:0;myBaseDamage:5}
P1OnlyActions: true
WithP1GroundArena: TWI_062:1:1

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1BASEDMG:5

---

# Restore_WhileUndamaged
#// TWI_062 Daughter of Dathomir (Unit 3/5, Ground) — "While this unit is undamaged, it gains Restore 2."
#// Undamaged, she attacks P2's base: Restore 2 heals 2 from P1's base (pre-damaged to 5 → 3).

## GIVEN
CommonSetup: bbk/grw/{myResources:0;myBaseDamage:5}
P1OnlyActions: true
WithP1GroundArena: TWI_062:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1BASEDMG:3
