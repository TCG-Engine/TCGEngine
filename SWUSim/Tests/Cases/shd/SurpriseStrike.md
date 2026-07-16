# AttackBuff
#// SHD_231 Surprise Strike (2-cost event, Cunning) — "Attack with a unit. It gets +3/+0 for this attack."
#// SOR_095 (3 power) attacks the base at 6.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_231
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:6
