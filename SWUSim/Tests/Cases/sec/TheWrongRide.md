# Exhaust2EnemyResources
#// SEC_235 The Wrong Ride (event, cost 3) — Exhaust 2 enemy resources. P2 has 4 ready resources → 2
#//   after.

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
P1OnlyActions: true
WithP2Resources: 4:SOR_046:1
WithP1Hand: SEC_235

## WHEN
- P1>PlayHand:0

## EXPECT
P2RESAVAILABLE:2
