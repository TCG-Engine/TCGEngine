# GrantsRestore
#// SOR_070 Devotion grants Restore 2 to its host (upgrade keyword-grant guard)
#// P1 has a vanilla Battlefield Marine (SOR_095, 3/3, no innate Restore) with
#// Devotion (SOR_070, +1/+1) attached → it gains Restore 2. When it attacks, heal 2
#// from its controller's base. P1 base starts at 3 damage → heals to 1. The host's
#// 3+1=4 power hits P2's base for 4.
#// (Contrast: without Devotion P1's base would stay at 3 and P2 base take 3.)

## GIVEN
CommonSetup: ggw/grw/{myBaseDamage:3}
WithP1GroundArena: SOR_095:1:0    # Battlefield Marine (3/3), ready
WithP1GroundArenaUpgrade: 0:SOR_070   # Devotion → host gains Restore 2

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:1
P2BASEDMG:4
