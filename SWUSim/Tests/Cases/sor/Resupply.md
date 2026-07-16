# PutsSelfAsResource
#// SOR_126 Resupply (Event, cost 3) — Put this event into play as a resource. Playing it costs
#// 3 (the 3 ready resources are exhausted), then Resupply itself enters the resource zone EXHAUSTED
#// ("into play as a resource" — no "ready" wording → exhausted): resources 3 → 4, all exhausted.

## GIVEN
CommonSetup: ggw/ggw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_126

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESCOUNT:4
P1RESAVAILABLE:0
P1HANDCOUNT:0
