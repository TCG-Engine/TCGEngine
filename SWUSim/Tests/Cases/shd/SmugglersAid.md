# Heal3OwnBase
#// SHD_252 Smuggler's Aid (1-cost event) — "Heal 3 damage from your base." 5 → 2.

## GIVEN
CommonSetup: gyw/gyw/{myResources:1;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: SHD_252

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:2
P1DISCARDCOUNT:1
