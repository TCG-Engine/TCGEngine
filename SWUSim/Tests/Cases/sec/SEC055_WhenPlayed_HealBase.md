# SEC_055 Dhani Pilgrim (Ground, 1/3) — When Played: heal 1 damage from your base. Base starts at 3
#   damage → 2 after playing SEC_055.

## GIVEN
CommonSetup: bbk/rrk/{myResources:1;myBaseDamage:3}
P1OnlyActions: true
WithP1Hand: SEC_055

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:2
