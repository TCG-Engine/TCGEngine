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

---

# OppHasNoReadyResources_NoEffect
#// SEC_235 The Wrong Ride — if the opponent has no ready resources there is nothing to exhaust; the event
#//   simply resolves to P1's discard. P2 has 3 resources, all already exhausted.

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
P1OnlyActions: true
WithP2Resources: 3:SOR_046:0
WithP1Hand: SEC_235

## WHEN
- P1>PlayHand:0

## EXPECT
P2RESAVAILABLE:0
P2RESCOUNT:3
P1DISCARDCOUNT:1
P1HANDCOUNT:0

---

# OppHasFewerThanTwoReady_ExhaustsWhatsAvailable
#// SEC_235 The Wrong Ride — "Exhaust 2 enemy resources" is an up-to-2 exhaust: with only 1 ready resource
#//   the one ready resource is exhausted (not all-or-nothing). P2 has exactly 1 ready resource → 0 after.

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
P1OnlyActions: true
WithP2Resources: 1:SOR_046:1
WithP1Hand: SEC_235

## WHEN
- P1>PlayHand:0

## EXPECT
P2RESAVAILABLE:0
P2RESCOUNT:1
