# SOR_126 Resupply (Event, cost 3) — Put this event into play as a resource. Playing it costs
# 3 (the 3 ready resources are exhausted), then Resupply itself enters the resource zone as a
# READY resource: total resources 3 → 4, of which 1 is ready (the new one).

## GIVEN
CommonSetup: ggw/ggw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_126

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESCOUNT:4
P1RESAVAILABLE:1
P1HANDCOUNT:0
