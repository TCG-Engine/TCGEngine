# SHD_066 Cargo Juggernaut — without another Vigilance unit, the base is not healed (stays at 5 damage).

## GIVEN
CommonSetup: bbw/bbw/{myResources:6;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: SHD_066
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:5
