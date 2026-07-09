# SHD_179 Desperate Attack (1-cost event, Aggression) — "Attack with a damaged unit. It gets +2/+0 for this
# attack." The damaged SOR_046 (3 power) attacks the base at 5.

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_179
WithP1GroundArena: SOR_046:1:2

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:5
