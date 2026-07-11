# SHD_080 Salacious Crumb (1-cost 1/3 ground) — "When Played: Heal 1 damage from your base." Base at 3
# damage is healed to 2.

## GIVEN
CommonSetup: ggk/ggk/{myResources:1;myBaseDamage:3}
P1OnlyActions: true
WithP1Hand: SHD_080

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:2
